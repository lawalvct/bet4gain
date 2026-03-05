<?php

namespace App\Services;

use App\Enums\BetStatus;
use App\Enums\GameRoundStatus;
use App\Events\GameBettingStarted;
use App\Events\GameCountdown;
use App\Events\GameCrashed;
use App\Events\GameMultiplierUpdated;
use App\Events\GameStarted;
use App\Models\Bet;
use App\Models\GameRound;
use App\Models\ProvablyFairSeed;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Core game engine.
 *
 * Manages the full round lifecycle:
 *   WAITING → BETTING → RUNNING → CRASHED → (back to WAITING)
 *
 * Round timing (configurable):
 *   - Waiting phase  : GAME_WAITING_DURATION  (default: 5 s)
 *   - Betting phase  : GAME_BETTING_DURATION  (default: 10 s)
 *   - Running phase  : until crash point reached
 *   - Broadcast rate : GAME_TICK_RATE         (default: 100 ms)
 *
 * Multiplier formula (time-based, authoritative on server):
 *   multiplier = max(1.0, exp(k * t/1000))
 *   where k = 0.00006 * 1000 = 0.06 and t is elapsed ms
 *   This gives ~2x at ~11.5s, ~10x at ~38s
 */
class GameEngine
{
    private const K = 0.06; // curve exponent constant

    public static function multiplierAtMs(int $elapsedMs): float
    {
        return max(1.0, exp(self::K * ($elapsedMs / 1000)));
    }

    /**
     * Calculate how many milliseconds until the crash point is reached.
     */
    public static function msToCrash(float $crashPoint): int
    {
        if ($crashPoint <= 1.0) return 0;
        return (int) (log($crashPoint) / self::K * 1000);
    }

    /**
     * Create a new GameRound record with provably fair seeds pre-seeded.
     */
    public static function createRound(): GameRound
    {
        $serverSeed = ProvablyFairService::generateServerSeed();
        $clientSeed = 'house'; // house seed for server-run rounds
        $nonce      = 0;
        $crashPoint = ProvablyFairService::calculateCrashPoint($serverSeed, $clientSeed, $nonce);
        $roundHash  = ProvablyFairService::hashServerSeed($serverSeed);

        return GameRound::create([
            'round_hash'  => $roundHash,
            'server_seed' => $serverSeed,
            'client_seed' => $clientSeed,
            'nonce'       => $nonce,
            'crash_point' => $crashPoint,
            'status'      => GameRoundStatus::Waiting,
        ]);
    }

    /**
     * Transition round to BETTING phase and broadcast.
     */
    public static function startBetting(GameRound $round): void
    {
        $round->update(['status' => GameRoundStatus::Betting]);

        $duration = config('game.betting_duration', 10);

        broadcast(new GameBettingStarted(
            roundId:        $round->id,
            serverSeedHash: $round->round_hash,
            bettingDuration: $duration,
        ));
    }

    /**
     * Transition round to RUNNING and broadcast game start.
     */
    public static function startRound(GameRound $round): void
    {
        $round->update([
            'status'     => GameRoundStatus::Running,
            'started_at' => now(),
        ]);

        broadcast(new GameStarted(
            roundId:        $round->id,
            serverSeedHash: $round->round_hash,
        ));
    }

    /**
     * The main running loop — broadcasts multiplier every tick ms.
     * Blocks until the crash point is reached.
     *
     * @param  GameRound  $round
     * @param  callable|null  $onTick  Optional callback on each tick (for testing/testing)
     */
    public static function runUntilCrash(GameRound $round, ?callable $onTick = null): void
    {
        $tickMs    = config('game.tick_rate', 100);
        $crashMs   = self::msToCrash((float) $round->crash_point);
        $startTime = microtime(true);

        while (true) {
            $elapsedMs  = (int) ((microtime(true) - $startTime) * 1000);
            $multiplier = self::multiplierAtMs($elapsedMs);

            // Check for auto-cashouts on each tick
            self::processAutoCashouts($round, $multiplier);

            if ($onTick) $onTick($multiplier, $elapsedMs);

            broadcast(new GameMultiplierUpdated(
                roundId:    $round->id,
                multiplier: $multiplier,
                elapsedMs:  $elapsedMs,
            ));

            // Crash condition: elapsed ≥ crash duration OR we've passed the crash point multiplier
            if ($elapsedMs >= $crashMs || $multiplier >= (float) $round->crash_point) {
                break;
            }

            // Sleep until next tick (accounting for time spent above)
            $nextTick  = $startTime + (($elapsedMs + $tickMs) / 1000);
            $sleepUsec = (int) (($nextTick - microtime(true)) * 1_000_000);
            if ($sleepUsec > 0) {
                usleep($sleepUsec);
            }
        }
    }

    /**
     * Process auto-cashouts for active bets at the current multiplier.
     */
    public static function processAutoCashouts(GameRound $round, float $multiplier): void
    {
        $eligibleBets = Bet::where('game_round_id', $round->id)
            ->where('status', BetStatus::Active)
            ->whereNotNull('auto_cashout_at')
            ->where('auto_cashout_at', '<=', $multiplier)
            ->get();

        foreach ($eligibleBets as $bet) {
            try {
                self::cashOutBet($bet, $multiplier, isAuto: true);
            } catch (\Throwable $e) {
                Log::error("Auto-cashout failed for bet #{$bet->id}: " . $e->getMessage());
            }
        }
    }

    /**
     * Cash out a bet at a given multiplier (manual or auto).
     */
    public static function cashOutBet(Bet $bet, float $multiplier, bool $isAuto = false): void
    {
        DB::transaction(function () use ($bet, $multiplier, $isAuto) {
            $payout = round($bet->amount * $multiplier, 4);

            $bet->update([
                'status'       => BetStatus::Won,
                'cashed_out_at' => $multiplier,
                'payout'       => $payout,
                'is_auto'      => $isAuto,
            ]);

            // Credit coins back to user
            $bet->user->coinBalance()->increment('balance', $payout);
        });
    }

    /**
     * Crash the round: mark all uncashed bets as lost, persist crash data, broadcast.
     */
    public static function crashRound(GameRound $round): void
    {
        $elapsedMs = $round->started_at
            ? (int) ($round->started_at->diffInMilliseconds(now()))
            : self::msToCrash((float) $round->crash_point);

        DB::transaction(function () use ($round, $elapsedMs) {
            // Mark all active (uncashed) bets as lost
            Bet::where('game_round_id', $round->id)
                ->where('status', BetStatus::Active)
                ->update(['status' => BetStatus::Lost]);

            $round->update([
                'status'     => GameRoundStatus::Crashed,
                'crashed_at' => now(),
                'duration_ms' => $elapsedMs,
            ]);
        });

        broadcast(new GameCrashed(
            roundId:    $round->id,
            crashPoint: (float) $round->crash_point,
            serverSeed: $round->server_seed,
            clientSeed: $round->client_seed,
            nonce:      $round->nonce,
            durationMs: $elapsedMs,
        ));
    }

    /**
     * Get current active round (if any).
     */
    public static function getCurrentRound(): ?GameRound
    {
        return GameRound::whereIn('status', [
            GameRoundStatus::Waiting,
            GameRoundStatus::Betting,
            GameRoundStatus::Running,
        ])->latest()->first();
    }
}
