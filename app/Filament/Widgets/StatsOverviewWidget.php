<?php

namespace App\Filament\Widgets;

use App\Models\Bet;
use App\Models\GameRound;
use App\Models\Transaction;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $todayStart = now()->startOfDay();
        $weekStart = now()->startOfWeek();
        $monthStart = now()->startOfMonth();

        // Revenue today
        $revenueToday = Transaction::where('type', 'deposit')
            ->where('status', 'completed')
            ->where('created_at', '>=', $todayStart)
            ->sum('amount');

        // Active users (seen in last 15 min)
        $activeUsers = User::where('last_seen_at', '>=', now()->subMinutes(15))
            ->where('is_banned', false)
            ->count();

        // Total bets today
        $betsToday = Bet::where('created_at', '>=', $todayStart)->count();

        // House profit today (total bet amounts - total payouts)
        $totalWagered = Bet::where('created_at', '>=', $todayStart)->sum('amount');
        $totalPayouts = Bet::where('created_at', '>=', $todayStart)
            ->whereNotNull('payout')
            ->sum('payout');
        $houseProfit = $totalWagered - $totalPayouts;

        // Weekly comparisons for chart
        $weeklyBets = [];
        $weeklyRevenue = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = now()->subDays($i);
            $weeklyBets[] = Bet::whereDate('created_at', $day)->count();
            $weeklyRevenue[] = (float) Transaction::where('type', 'deposit')
                ->where('status', 'completed')
                ->whereDate('created_at', $day)
                ->sum('amount');
        }

        // Total registered users
        $totalUsers = User::where('is_guest', false)->count();
        $newUsersToday = User::where('is_guest', false)
            ->where('created_at', '>=', $todayStart)
            ->count();

        // Total rounds today
        $roundsToday = GameRound::where('created_at', '>=', $todayStart)->count();

        // Pending withdrawals
        $pendingWithdrawals = Transaction::where('type', 'withdrawal')
            ->where('status', 'pending')
            ->count();

        return [
            Stat::make('Revenue Today', number_format($revenueToday, 2))
                ->description('Deposits received')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->chart($weeklyRevenue),

            Stat::make('Active Users', $activeUsers)
                ->description("Total: {$totalUsers} | New today: {$newUsersToday}")
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),

            Stat::make('Bets Today', number_format($betsToday))
                ->description("{$roundsToday} rounds played")
                ->descriptionIcon('heroicon-m-fire')
                ->color('warning')
                ->chart($weeklyBets),

            Stat::make('House Profit', number_format($houseProfit, 2))
                ->description($houseProfit >= 0 ? 'Profitable' : 'Loss')
                ->descriptionIcon($houseProfit >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($houseProfit >= 0 ? 'success' : 'danger'),

            Stat::make('Pending Withdrawals', $pendingWithdrawals)
                ->description('Requires attention')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pendingWithdrawals > 0 ? 'warning' : 'gray'),
        ];
    }
}
