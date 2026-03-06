<?php

namespace App\Http\Controllers;

use App\Enums\BetStatus;
use App\Enums\GameRoundStatus;
use App\Events\BetCashedOut;
use App\Events\BetPlaced;
use App\Models\Bet;
use App\Models\GameRound;
use App\Services\AntiCheatService;
use App\Services\GameEngine;
use App\Services\ResponsibleGamingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class BetController extends Controller
{
    /**
     * POST /api/game/bet
     *
     * Place a bet on the current round.
     * Allowed during WAITING or BETTING phases only.
     */
    public function placeBet(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount'          => 'required|numeric|min:' . config('game.min_bet') . '|max:' . config('game.max_bet'),
            'auto_cashout_at' => 'nullable|numeric|min:1.01',
            'bet_slot'        => 'required|integer|in:1,2',
            'currency'        => 'nullable|string|in:COINS,DEMO,NGN',
        ]);

        $user     = $request->user();
        $amount   = (float) $validated['amount'];
        $slot     = (int) $validated['bet_slot'];
        $currency = $validated['currency'] ?? 'COINS';

        // ── Phase 10: Responsible Gaming — Bet limit check ──
        $rgService = app(ResponsibleGamingService::class);
        $betLimitCheck = $rgService->checkBetLimit($user, $amount);
        if (!$betLimitCheck['allowed']) {
            return response()->json([
                'message' => $betLimitCheck['reason'],
            ], 422);
        }

        // Find current round accepting bets
        $round = GameRound::whereIn('status', [
            GameRoundStatus::Waiting,
            GameRoundStatus::Betting,
        ])->latest()->first();

        if (!$round) {
            return response()->json([
                'message' => 'No active round accepting bets.',
            ], 422);
        }

        // Duplicate slot check
        $existingBet = Bet::where('user_id', $user->id)
            ->where('game_round_id', $round->id)
            ->where('bet_slot', $slot)
            ->whereNotIn('status', [BetStatus::Cancelled])
            ->first();

        if ($existingBet) {
            return response()->json([
                'message' => 'You already have a bet in slot ' . $slot . ' for this round.',
            ], 422);
        }

        // Max bets per round check
        $userBetCount = Bet::where('user_id', $user->id)
            ->where('game_round_id', $round->id)
            ->whereNotIn('status', [BetStatus::Cancelled])
            ->count();

        if ($userBetCount >= config('game.max_bets_per_round', 2)) {
            return response()->json([
                'message' => 'Maximum bets per round reached.',
            ], 422);
        }

        // Balance check & deduction inside transaction
        try {
            $bet = DB::transaction(function () use ($user, $round, $amount, $slot, $currency, $validated) {
                if ($currency === 'NGN') {
                    $wallet = $user->wallet()->lockForUpdate()->first();

                    if (!$wallet || !$wallet->hasEnough($amount)) {
                        throw ValidationException::withMessages([
                            'amount' => ['Insufficient wallet balance.'],
                        ]);
                    }
                    $wallet->decrement('balance', $amount);
                } else {
                    $coinBalance = $user->coinBalance()->lockForUpdate()->first();

                    if (!$coinBalance) {
                        throw ValidationException::withMessages([
                            'amount' => ['No coin balance found. Please contact support.'],
                        ]);
                    }

                    if ($currency === 'DEMO') {
                        if (!$coinBalance->hasDemoEnough($amount)) {
                            throw ValidationException::withMessages([
                                'amount' => ['Insufficient demo balance.'],
                            ]);
                        }
                        $coinBalance->decrement('demo_balance', $amount);
                    } else {
                        if (!$coinBalance->hasEnough($amount)) {
                            throw ValidationException::withMessages([
                                'amount' => ['Insufficient coin balance.'],
                            ]);
                        }
                        $coinBalance->decrement('balance', $amount);
                    }
                }

                return Bet::create([
                    'user_id'         => $user->id,
                    'game_round_id'   => $round->id,
                    'amount'          => $amount,
                    'currency'        => $currency,
                    'auto_cashout_at' => $validated['auto_cashout_at'] ?? null,
                    'bet_slot'        => $slot,
                    'status'          => BetStatus::Pending,
                ]);
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Bet placement failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to place bet. Please try again.',
            ], 500);
        }

        // Activate the bet if round is already in BETTING phase
        if ($round->status === GameRoundStatus::Betting) {
            $bet->update(['status' => BetStatus::Active]);
        }

        // Broadcast bet to all clients for live bets feed
        broadcast(new BetPlaced(
            betId:    $bet->id,
            roundId:  $round->id,
            username: $user->username,
            avatar:   $user->avatar_url,
            amount:   $amount,
            betSlot:  $slot,
            currency: $currency,
        ));

        $bet->load('user:id,username,avatar');

        return response()->json([
            'message' => 'Bet placed successfully.',
            'data'    => [
                'id'              => $bet->id,
                'game_round_id'   => $bet->game_round_id,
                'amount'          => $bet->amount,
                'currency'        => $bet->currency,
                'auto_cashout_at' => $bet->auto_cashout_at,
                'bet_slot'        => $bet->bet_slot,
                'status'          => $bet->status->value,
                'username'        => $bet->user->username,
                'avatar'          => $bet->user->avatar_url,
            ],
        ], 201);
    }

    /**
     * POST /api/game/cashout
     *
     * Cash out an active bet during RUNNING phase.
     */
    public function cashout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'bet_slot' => 'required|integer|in:1,2',
        ]);

        $user = $request->user();
        $slot = (int) $validated['bet_slot'];

        // Find the user's active bet for the current running round
        $round = GameRound::where('status', GameRoundStatus::Running)
            ->latest()
            ->first();

        if (!$round) {
            return response()->json([
                'message' => 'No round currently running.',
            ], 422);
        }

        $bet = Bet::where('user_id', $user->id)
            ->where('game_round_id', $round->id)
            ->where('bet_slot', $slot)
            ->where('status', BetStatus::Active)
            ->first();

        if (!$bet) {
            return response()->json([
                'message' => 'No active bet found in slot ' . $slot . '.',
            ], 422);
        }

        // Calculate current multiplier from elapsed time
        $elapsedMs  = $round->started_at
            ? (int) ($round->started_at->diffInMilliseconds(now()))
            : 0;
        $multiplier = GameEngine::multiplierAtMs($elapsedMs);

        // Ensure multiplier hasn't exceeded crash point (race condition guard)
        if ($multiplier >= (float) $round->crash_point) {
            return response()->json([
                'message' => 'Round has already crashed.',
            ], 422);
        }

        try {
            GameEngine::cashOutBet($bet, $multiplier, isAuto: false);
        } catch (\Throwable $e) {
            Log::error("Cashout failed for bet #{$bet->id}: " . $e->getMessage());
            return response()->json([
                'message' => 'Cashout failed. Please try again.',
            ], 500);
        }

        $bet->refresh();

        // Broadcast cashout to all clients
        broadcast(new BetCashedOut(
            betId:      $bet->id,
            roundId:    $round->id,
            username:   $user->username,
            amount:     (float) $bet->amount,
            cashedOutAt: (float) $bet->cashed_out_at,
            payout:     (float) $bet->payout,
            isAuto:     false,
        ));

        return response()->json([
            'message' => 'Cashed out successfully!',
            'data'    => [
                'id'            => $bet->id,
                'amount'        => $bet->amount,
                'cashed_out_at' => $bet->cashed_out_at,
                'payout'        => $bet->payout,
                'profit'        => round((float) $bet->payout - (float) $bet->amount, 4),
                'status'        => $bet->status->value,
                'bet_slot'      => $bet->bet_slot,
            ],
        ]);
    }

    /**
     * POST /api/game/cancel-bet
     *
     * Cancel a pending bet before the round starts running.
     */
    public function cancelBet(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'bet_slot' => 'required|integer|in:1,2',
        ]);

        $user = $request->user();
        $slot = (int) $validated['bet_slot'];

        // Find current round (waiting or betting)
        $round = GameRound::whereIn('status', [
            GameRoundStatus::Waiting,
            GameRoundStatus::Betting,
        ])->latest()->first();

        if (!$round) {
            return response()->json([
                'message' => 'No active round to cancel bet in.',
            ], 422);
        }

        $bet = Bet::where('user_id', $user->id)
            ->where('game_round_id', $round->id)
            ->where('bet_slot', $slot)
            ->whereIn('status', [BetStatus::Pending, BetStatus::Active])
            ->first();

        if (!$bet) {
            return response()->json([
                'message' => 'No cancellable bet found in slot ' . $slot . '.',
            ], 422);
        }

        // Cannot cancel if round is running
        if ($round->status === GameRoundStatus::Running) {
            return response()->json([
                'message' => 'Cannot cancel bet during a running round. Cash out instead.',
            ], 422);
        }

        try {
            DB::transaction(function () use ($bet, $user) {
                if ($bet->currency === 'NGN') {
                    $wallet = $user->wallet()->lockForUpdate()->first();
                    $wallet->increment('balance', (float) $bet->amount);
                } else {
                    $coinBalance = $user->coinBalance()->lockForUpdate()->first();

                    if ($bet->currency === 'DEMO') {
                        $coinBalance->increment('demo_balance', (float) $bet->amount);
                    } else {
                        $coinBalance->increment('balance', (float) $bet->amount);
                    }
                }

                $bet->update(['status' => BetStatus::Cancelled]);
            });
        } catch (\Throwable $e) {
            Log::error("Cancel bet failed for bet #{$bet->id}: " . $e->getMessage());
            return response()->json([
                'message' => 'Failed to cancel bet.',
            ], 500);
        }

        return response()->json([
            'message' => 'Bet cancelled. Funds returned.',
            'data'    => [
                'id'       => $bet->id,
                'amount'   => $bet->amount,
                'status'   => BetStatus::Cancelled->value,
                'bet_slot' => $bet->bet_slot,
            ],
        ]);
    }
}
