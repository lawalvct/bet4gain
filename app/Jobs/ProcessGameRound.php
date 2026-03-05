<?php

namespace App\Jobs;

use App\Events\GameCountdown;
use App\Services\GameEngine;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Processes a single complete game round lifecycle:
 *   1. Create round record
 *   2. Broadcast waiting countdown
 *   3. Broadcast betting started → wait betting duration
 *   4. Start round → run multiplier loop until crash
 *   5. Crash round → wait then dispatch next round
 *
 * This job runs synchronously within a long-lived process.
 * Use `php artisan game:start` to run the continuous loop.
 */
class ProcessGameRound implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;
    public int $timeout = 120; // 2 minutes max per round

    public function handle(): void
    {
        try {
            $this->runRound();
        } catch (\Throwable $e) {
            Log::error('[GameRound] Error during round: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
        } finally {
            // Always schedule the next round
            $waitingDuration = config('game.waiting_duration', 5);
            self::dispatch()->delay(now()->addSeconds($waitingDuration));
        }
    }

    private function runRound(): void
    {
        $bettingDuration = config('game.betting_duration', 10);
        $waitingDuration = config('game.waiting_duration', 5);

        // --- WAITING PHASE ---
        $round = GameEngine::createRound();

        Log::info("[GameRound] Round #{$round->id} created. Crash point: {$round->crash_point}x");

        for ($i = $waitingDuration; $i >= 1; $i--) {
            broadcast(new GameCountdown(secondsLeft: $i))->toOthers();
            sleep(1);
        }

        // --- BETTING PHASE ---
        GameEngine::startBetting($round);
        Log::info("[GameRound] Round #{$round->id} BETTING started.");

        for ($i = $bettingDuration; $i >= 1; $i--) {
            sleep(1);
        }

        // --- RUNNING PHASE ---
        GameEngine::startRound($round);
        Log::info("[GameRound] Round #{$round->id} STARTED.");

        GameEngine::runUntilCrash($round);

        // --- CRASHED ---
        GameEngine::crashRound($round);
        Log::info("[GameRound] Round #{$round->id} CRASHED at {$round->crash_point}x");
    }
}
