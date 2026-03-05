<?php

namespace App\Http\Controllers;

use App\Enums\BetStatus;
use App\Enums\GameRoundStatus;
use App\Models\Bet;
use App\Models\GameRound;
use App\Services\GameEngine;
use App\Services\ProvablyFairService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GameController extends Controller
{
    /**
     * GET /api/game/state
     * Returns the current active round state.
     */
    public function state(): JsonResponse
    {
        $round = GameEngine::getCurrentRound();

        if (!$round) {
            return response()->json(['data' => null]);
        }

        return response()->json([
            'data' => [
                'round_id'         => $round->id,
                'status'           => $round->status->value,
                'server_seed_hash' => $round->round_hash,
                'started_at'       => $round->started_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * GET /api/game/history
     * Returns the last 50 crashed rounds.
     */
    public function history(): JsonResponse
    {
        $rounds = GameRound::where('status', GameRoundStatus::Crashed)
            ->orderByDesc('id')
            ->limit(50)
            ->get(['id', 'crash_point', 'crashed_at', 'duration_ms']);

        return response()->json(['data' => $rounds]);
    }

    /**
     * GET /api/game/round/{id}
     * Returns a specific round's details (after crash — for verification).
     */
    public function round(int $id): JsonResponse
    {
        $round = GameRound::findOrFail($id);

        // Only reveal seeds after crash
        $data = [
            'id'               => $round->id,
            'status'           => $round->status->value,
            'crash_point'      => $round->crash_point,
            'server_seed_hash' => $round->round_hash,
            'started_at'       => $round->started_at?->toIso8601String(),
            'crashed_at'       => $round->crashed_at?->toIso8601String(),
            'duration_ms'      => $round->duration_ms,
            'bet_count'        => $round->bets()->count(),
        ];

        // Reveal seeds for completed rounds
        if ($round->status === GameRoundStatus::Crashed) {
            $data['server_seed'] = $round->server_seed;
            $data['client_seed'] = $round->client_seed;
            $data['nonce']       = $round->nonce;
        }

        return response()->json(['data' => $data]);
    }

    /**
     * GET /api/game/round/{id}/bets
     * Returns all bets for a specific round (for round history view).
     */
    public function roundBets(int $id): JsonResponse
    {
        $bets = Bet::where('game_round_id', $id)
            ->with('user:id,username,avatar')
            ->orderByDesc('payout')
            ->get()
            ->map(fn($bet) => [
                'id'            => $bet->id,
                'username'      => $bet->user->username,
                'avatar'        => $bet->user->avatar,
                'amount'        => $bet->amount,
                'payout'        => $bet->payout,
                'cashed_out_at' => $bet->cashed_out_at,
                'status'        => $bet->status->value,
                'is_auto'       => $bet->is_auto,
            ]);

        return response()->json(['data' => $bets]);
    }

    /**
     * POST /api/game/verify
     * Verify crash point independently (provably fair verification).
     */
    public function verify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'server_seed'      => 'required|string|size:64',
            'server_seed_hash' => 'required|string|size:64',
            'client_seed'      => 'required|string|min:1|max:64',
            'nonce'            => 'required|integer|min:0',
        ]);

        $result = ProvablyFairService::verify(
            serverSeed:      $validated['server_seed'],
            serverSeedHash:  $validated['server_seed_hash'],
            clientSeed:      $validated['client_seed'],
            nonce:           $validated['nonce'],
        );

        return response()->json(['data' => $result]);
    }

    /**
     * GET /api/game/live-bets
     * Returns active bets for the current running round.
     */
    public function liveBets(): JsonResponse
    {
        $round = GameEngine::getCurrentRound();

        if (!$round) {
            return response()->json(['data' => []]);
        }

        $bets = Bet::where('game_round_id', $round->id)
            ->with('user:id,username,avatar')
            ->orderByDesc('amount')
            ->get()
            ->map(fn($bet) => [
                'id'            => $bet->id,
                'username'      => $bet->user->username,
                'avatar'        => $bet->user->avatar,
                'amount'        => $bet->amount,
                'cashed_out_at' => $bet->cashed_out_at,
                'payout'        => $bet->payout,
                'status'        => $bet->status->value,
                'bet_slot'      => $bet->bet_slot,
            ]);

        return response()->json([
            'data'     => $bets,
            'round_id' => $round->id,
        ]);
    }
}
