<?php

namespace App\Services;

use App\Enums\BetStatus;
use App\Enums\LeaderboardPeriod;
use App\Models\Bet;
use App\Models\LeaderboardEntry;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class LeaderboardService
{
    /**
     * Recalculate leaderboard entries for a given period.
     */
    public function calculate(LeaderboardPeriod $period): int
    {
        $dateFrom = $this->getDateFrom($period);

        $query = Bet::query()
            ->select([
                'user_id',
                DB::raw('SUM(amount) as total_wagered'),
                DB::raw('SUM(CASE WHEN status = "won" THEN payout ELSE 0 END) as total_won'),
                DB::raw('SUM(CASE WHEN status = "won" THEN (payout - amount) ELSE -amount END) as total_profit'),
                DB::raw('MAX(CASE WHEN status = "won" THEN cashed_out_at ELSE 0 END) as best_multiplier'),
                DB::raw('COUNT(*) as total_games'),
                DB::raw('SUM(CASE WHEN status = "won" THEN 1 ELSE 0 END) as win_count'),
            ])
            ->whereIn('status', [BetStatus::Won, BetStatus::Lost])
            ->where('currency', '!=', 'DEMO')
            ->groupBy('user_id');

        if ($dateFrom) {
            $query->where('created_at', '>=', $dateFrom);
        }

        $stats = $query->get();

        $now = now();

        DB::transaction(function () use ($stats, $period, $now) {
            // Delete old entries for this period
            LeaderboardEntry::where('period', $period->value)->delete();

            // Batch insert new entries
            $entries = $stats->map(fn($row) => [
                'user_id'         => $row->user_id,
                'period'          => $period->value,
                'total_wagered'   => $row->total_wagered ?? 0,
                'total_won'       => $row->total_won ?? 0,
                'total_profit'    => $row->total_profit ?? 0,
                'best_multiplier' => $row->best_multiplier ?? 0,
                'total_games'     => $row->total_games ?? 0,
                'win_count'       => $row->win_count ?? 0,
                'calculated_at'   => $now,
            ])->toArray();

            if (!empty($entries)) {
                // Insert in chunks to avoid memory issues
                foreach (array_chunk($entries, 500) as $chunk) {
                    LeaderboardEntry::insert($chunk);
                }
            }
        });

        // Clear cache for this period
        $this->clearCache($period);

        return $stats->count();
    }

    /**
     * Calculate all periods.
     */
    public function calculateAll(): array
    {
        $results = [];

        foreach (LeaderboardPeriod::cases() as $period) {
            $count = $this->calculate($period);
            $results[$period->value] = $count;
        }

        return $results;
    }

    /**
     * Get cached leaderboard entries for a period.
     */
    public function getLeaderboard(LeaderboardPeriod $period, int $limit = null): array
    {
        $limit = $limit ?? config('game.leaderboard_top_count', 50);
        $ttl   = config('game.leaderboard_cache_ttl', 300);
        $cacheKey = "leaderboard:{$period->value}";
        $data = $this->buildLeaderboardData($period, $limit);

        if (empty($data) && $this->hasLeaderboardSourceData($period)) {
            $this->calculate($period);
            $data = $this->buildLeaderboardData($period, $limit);
        }

        Cache::put($cacheKey, $data, $ttl);

        return $data;
    }

    /**
     * Get a player's personal statistics.
     */
    public function getPlayerStats(int $userId): array
    {
        $allTime = Bet::where('user_id', $userId)
            ->where('currency', '!=', 'DEMO')
            ->whereIn('status', [BetStatus::Won, BetStatus::Lost])
            ->select([
                DB::raw('SUM(amount) as total_wagered'),
                DB::raw('SUM(CASE WHEN status = "won" THEN payout ELSE 0 END) as total_won'),
                DB::raw('SUM(CASE WHEN status = "won" THEN (payout - amount) ELSE -amount END) as total_profit'),
                DB::raw('MAX(CASE WHEN status = "won" THEN cashed_out_at ELSE 0 END) as best_multiplier'),
                DB::raw('COUNT(*) as total_games'),
                DB::raw('SUM(CASE WHEN status = "won" THEN 1 ELSE 0 END) as win_count'),
                DB::raw('MAX(CASE WHEN status = "won" THEN payout ELSE 0 END) as biggest_win'),
            ])
            ->first();

        // Recent 7-day stats for chart
        $dailyStats = Bet::where('user_id', $userId)
            ->where('currency', '!=', 'DEMO')
            ->whereIn('status', [BetStatus::Won, BetStatus::Lost])
            ->where('created_at', '>=', now()->subDays(30))
            ->select([
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as games'),
                DB::raw('SUM(CASE WHEN status = "won" THEN 1 ELSE 0 END) as wins'),
                DB::raw('SUM(CASE WHEN status = "won" THEN (payout - amount) ELSE -amount END) as profit'),
            ])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        // Favorite bet amount (mode)
        $favoriteBet = Bet::where('user_id', $userId)
            ->where('currency', '!=', 'DEMO')
            ->select(['amount', DB::raw('COUNT(*) as cnt')])
            ->groupBy('amount')
            ->orderByDesc('cnt')
            ->first();

        // Leaderboard rank (all-time)
        $rank = LeaderboardEntry::where('period', LeaderboardPeriod::AllTime->value)
            ->where('total_profit', '>', ($allTime->total_profit ?? 0))
            ->count() + 1;

        $totalGames = (int) ($allTime->total_games ?? 0);
        $winCount   = (int) ($allTime->win_count ?? 0);

        return [
            'total_wagered'   => (float) ($allTime->total_wagered ?? 0),
            'total_won'       => (float) ($allTime->total_won ?? 0),
            'total_profit'    => (float) ($allTime->total_profit ?? 0),
            'best_multiplier' => (float) ($allTime->best_multiplier ?? 0),
            'biggest_win'     => (float) ($allTime->biggest_win ?? 0),
            'total_games'     => $totalGames,
            'win_count'       => $winCount,
            'win_rate'        => $totalGames > 0 ? round(($winCount / $totalGames) * 100, 1) : 0,
            'favorite_bet'    => (float) ($favoriteBet->amount ?? 0),
            'rank'            => $rank,
            'daily_stats'     => $dailyStats->toArray(),
        ];
    }

    /**
     * Get player's bet history (paginated).
     */
    public function getPlayerBetHistory(int $userId, int $perPage = 20): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return Bet::where('user_id', $userId)
            ->with('gameRound:id,crash_point,round_hash')
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->through(fn($bet) => [
                'id'            => $bet->id,
                'round_id'      => $bet->game_round_id,
                'crash_point'   => $bet->gameRound?->crash_point,
                'round_hash'    => $bet->gameRound?->round_hash,
                'amount'        => (float) $bet->amount,
                'currency'      => $bet->currency,
                'cashed_out_at' => $bet->cashed_out_at ? (float) $bet->cashed_out_at : null,
                'payout'        => $bet->payout ? (float) $bet->payout : null,
                'profit'        => $bet->profit,
                'status'        => $bet->status->value,
                'is_auto'       => $bet->is_auto,
                'created_at'    => $bet->created_at->toIso8601String(),
            ]);
    }

    /**
     * Get live statistics for the stats bar.
     */
    public function getLiveStats(): array
    {
        $cacheKey = 'live_stats';
        $ttl = 30; // 30 seconds

        return Cache::remember($cacheKey, $ttl, function () {
            $today = Carbon::today();

            $todayStats = Bet::whereDate('created_at', $today)
                ->where('currency', '!=', 'DEMO')
                ->whereIn('status', [BetStatus::Won, BetStatus::Lost])
                ->select([
                    DB::raw('SUM(amount) as total_wagered'),
                    DB::raw('MAX(CASE WHEN status = "won" THEN payout ELSE 0 END) as biggest_win'),
                    DB::raw('COUNT(DISTINCT user_id) as unique_players'),
                    DB::raw('COUNT(*) as total_bets'),
                ])
                ->first();

            return [
                'total_wagered_today' => (float) ($todayStats->total_wagered ?? 0),
                'biggest_win_today'   => (float) ($todayStats->biggest_win ?? 0),
                'unique_players'      => (int) ($todayStats->unique_players ?? 0),
                'total_bets_today'    => (int) ($todayStats->total_bets ?? 0),
            ];
        });
    }

    /**
     * Get game round history (paginated) for the history page.
     */
    public function getGameHistory(int $perPage = 30): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return \App\Models\GameRound::where('status', 'crashed')
            ->withCount('bets')
            ->withSum('bets', 'amount')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->through(fn($round) => [
                'id'              => $round->id,
                'crash_point'     => (float) $round->crash_point,
                'round_hash'      => $round->round_hash,
                'bet_count'       => $round->bets_count,
                'total_wagered'   => (float) ($round->bets_sum_amount ?? 0),
                'crashed_at'      => $round->crashed_at?->toIso8601String(),
                'duration_ms'     => $round->duration_ms,
            ]);
    }

    // ── Private Helpers ──

    private function buildLeaderboardData(LeaderboardPeriod $period, int $limit): array
    {
        return LeaderboardEntry::where('period', $period->value)
            ->with('user:id,username,avatar')
            ->orderByDesc('total_profit')
            ->limit($limit)
            ->get()
            ->map(fn($entry) => [
                'rank'            => null,
                'user_id'         => $entry->user_id,
                'username'        => $entry->user?->username ?? 'Unknown',
                'avatar_url'      => $entry->user?->avatar_url ?? null,
                'total_wagered'   => (float) $entry->total_wagered,
                'total_won'       => (float) $entry->total_won,
                'total_profit'    => (float) $entry->total_profit,
                'best_multiplier' => (float) $entry->best_multiplier,
                'total_games'     => (int) $entry->total_games,
                'win_count'       => (int) $entry->win_count,
                'win_rate'        => $entry->win_rate,
            ])
            ->values()
            ->map(function ($entry, $index) {
                $entry['rank'] = $index + 1;

                return $entry;
            })
            ->toArray();
    }

    private function hasLeaderboardSourceData(LeaderboardPeriod $period): bool
    {
        $dateFrom = $this->getDateFrom($period);

        return Bet::query()
            ->whereIn('status', [BetStatus::Won, BetStatus::Lost])
            ->where('currency', '!=', 'DEMO')
            ->when($dateFrom, fn($query) => $query->where('created_at', '>=', $dateFrom))
            ->exists();
    }

    private function getDateFrom(LeaderboardPeriod $period): ?Carbon
    {
        return match ($period) {
            LeaderboardPeriod::Daily   => Carbon::today(),
            LeaderboardPeriod::Weekly  => Carbon::now()->startOfWeek(),
            LeaderboardPeriod::Monthly => Carbon::now()->startOfMonth(),
            LeaderboardPeriod::AllTime => null,
        };
    }

    private function clearCache(LeaderboardPeriod $period): void
    {
        Cache::forget("leaderboard:{$period->value}");
        Cache::forget('live_stats');
    }
}
