<?php

namespace App\Http\Controllers;

use App\Enums\LeaderboardPeriod;
use App\Services\LeaderboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaderboardController extends Controller
{
    public function __construct(
        private readonly LeaderboardService $leaderboardService,
    ) {}

    /**
     * GET /api/leaderboard/{period?}
     * Get leaderboard entries for a given period.
     */
    public function index(string $period = 'daily'): JsonResponse
    {
        $leaderboardPeriod = LeaderboardPeriod::tryFrom($period);

        if (!$leaderboardPeriod) {
            $leaderboardPeriod = LeaderboardPeriod::Daily;
        }

        $data = $this->leaderboardService->getLeaderboard($leaderboardPeriod);

        return response()->json(['data' => $data]);
    }

    /**
     * GET /api/stats/live
     * Get live statistics bar data.
     */
    public function liveStats(): JsonResponse
    {
        $data = $this->leaderboardService->getLiveStats();

        return response()->json(['data' => $data]);
    }

    /**
     * GET /api/stats/me
     * Get personal statistics for the authenticated user.
     */
    public function personalStats(Request $request): JsonResponse
    {
        $data = $this->leaderboardService->getPlayerStats($request->user()->id);

        return response()->json(['data' => $data]);
    }

    /**
     * GET /api/stats/player/{id}
     * Get public statistics for a specific player.
     */
    public function playerStats(int $id): JsonResponse
    {
        $user = \App\Models\User::findOrFail($id);

        $data = $this->leaderboardService->getPlayerStats($user->id);

        // Add user info
        $data['user'] = [
            'id'         => $user->id,
            'username'   => $user->username,
            'avatar_url' => $user->avatar_url,
            'role'       => $user->role->value ?? $user->role,
            'joined'     => $user->created_at->format('M Y'),
        ];

        return response()->json(['data' => $data]);
    }

    /**
     * GET /api/stats/my-bets
     * Get authenticated user's bet history (paginated).
     */
    public function myBets(Request $request): JsonResponse
    {
        $perPage = min((int) $request->get('per_page', 20), 50);

        $data = $this->leaderboardService->getPlayerBetHistory(
            $request->user()->id,
            $perPage,
        );

        return response()->json($data);
    }

    /**
     * GET /api/game/rounds
     * Paginated game round history for the history page.
     */
    public function gameHistory(Request $request): JsonResponse
    {
        $perPage = min((int) $request->get('per_page', 30), 50);

        $data = $this->leaderboardService->getGameHistory($perPage);

        return response()->json($data);
    }
}
