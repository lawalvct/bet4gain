<?php

namespace App\Filament\Pages;

use App\Models\Bet;
use App\Models\GameRound;
use App\Models\Transaction;
use App\Models\User;
use Filament\Pages\Page;

class Reports extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static string|\UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Reports & Analytics';

    protected string $view = 'filament.pages.reports';

    public array $profitReport = [];
    public array $playerActivity = [];
    public array $gameHealth = [];
    public array $paymentReport = [];
    public string $period = '7'; // days

    public function mount(): void
    {
        $this->loadReports();
    }

    public function updatedPeriod(): void
    {
        $this->loadReports();
    }

    protected function loadReports(): void
    {
        $days = (int) $this->period;
        $startDate = now()->subDays($days);

        $this->loadProfitReport($startDate);
        $this->loadPlayerActivity($startDate);
        $this->loadGameHealth($startDate);
        $this->loadPaymentReport($startDate);
    }

    protected function loadProfitReport($startDate): void
    {
        $totalWagered = Bet::where('created_at', '>=', $startDate)->sum('amount');
        $totalPayouts = Bet::where('created_at', '>=', $startDate)->whereNotNull('payout')->sum('payout');
        $houseProfit = $totalWagered - $totalPayouts;
        $houseEdgeActual = $totalWagered > 0 ? ($houseProfit / $totalWagered) * 100 : 0;

        $totalDeposits = Transaction::where('type', 'deposit')
            ->where('status', 'completed')
            ->where('created_at', '>=', $startDate)
            ->sum('amount');

        $totalWithdrawals = Transaction::where('type', 'withdrawal')
            ->where('status', 'completed')
            ->where('created_at', '>=', $startDate)
            ->sum('amount');

        $this->profitReport = [
            'total_wagered' => number_format($totalWagered, 2),
            'total_payouts' => number_format($totalPayouts, 2),
            'house_profit' => number_format($houseProfit, 2),
            'house_edge_actual' => number_format($houseEdgeActual, 2),
            'total_deposits' => number_format($totalDeposits, 2),
            'total_withdrawals' => number_format($totalWithdrawals, 2),
            'net_revenue' => number_format($totalDeposits - $totalWithdrawals, 2),
            'is_profitable' => $houseProfit >= 0,
        ];
    }

    protected function loadPlayerActivity($startDate): void
    {
        $newUsers = User::where('is_guest', false)
            ->where('created_at', '>=', $startDate)
            ->count();

        $activeUsers = User::where('last_seen_at', '>=', $startDate)
            ->where('is_guest', false)
            ->count();

        $totalUsers = User::where('is_guest', false)->count();

        $bettingUsers = Bet::where('created_at', '>=', $startDate)
            ->distinct('user_id')
            ->count('user_id');

        $avgBetAmount = Bet::where('created_at', '>=', $startDate)->avg('amount') ?? 0;

        $topWinners = Bet::where('created_at', '>=', $startDate)
            ->whereNotNull('payout')
            ->selectRaw('user_id, SUM(payout - amount) as total_profit')
            ->groupBy('user_id')
            ->orderByDesc('total_profit')
            ->limit(10)
            ->with('user:id,username')
            ->get()
            ->map(fn ($bet) => [
                'username' => $bet->user->username ?? 'Unknown',
                'profit' => number_format($bet->total_profit, 2),
            ])
            ->toArray();

        $this->playerActivity = [
            'new_users' => $newUsers,
            'active_users' => $activeUsers,
            'total_users' => $totalUsers,
            'betting_users' => $bettingUsers,
            'avg_bet_amount' => number_format($avgBetAmount, 2),
            'top_winners' => $topWinners,
        ];
    }

    protected function loadGameHealth($startDate): void
    {
        $totalRounds = GameRound::where('created_at', '>=', $startDate)->count();
        $avgCrashPoint = GameRound::where('created_at', '>=', $startDate)
            ->where('status', 'crashed')
            ->avg('crash_point') ?? 0;

        $crashDistribution = [
            'under_1_5x' => GameRound::where('created_at', '>=', $startDate)
                ->where('crash_point', '<', 1.5)->count(),
            '1_5x_to_2x' => GameRound::where('created_at', '>=', $startDate)
                ->whereBetween('crash_point', [1.5, 2])->count(),
            '2x_to_5x' => GameRound::where('created_at', '>=', $startDate)
                ->whereBetween('crash_point', [2, 5])->count(),
            '5x_to_10x' => GameRound::where('created_at', '>=', $startDate)
                ->whereBetween('crash_point', [5, 10])->count(),
            'over_10x' => GameRound::where('created_at', '>=', $startDate)
                ->where('crash_point', '>=', 10)->count(),
        ];

        $totalBets = Bet::where('created_at', '>=', $startDate)->count();
        $avgBetsPerRound = $totalRounds > 0 ? round($totalBets / $totalRounds, 1) : 0;

        $this->gameHealth = [
            'total_rounds' => $totalRounds,
            'avg_crash_point' => number_format($avgCrashPoint, 2),
            'crash_distribution' => $crashDistribution,
            'total_bets' => $totalBets,
            'avg_bets_per_round' => $avgBetsPerRound,
        ];
    }

    protected function loadPaymentReport($startDate): void
    {
        $depositsByGateway = Transaction::where('type', 'deposit')
            ->where('status', 'completed')
            ->where('created_at', '>=', $startDate)
            ->selectRaw('COALESCE(gateway, "manual") as gateway, COUNT(*) as count, SUM(amount) as total')
            ->groupBy('gateway')
            ->get()
            ->map(fn ($row) => [
                'gateway' => ucfirst($row->gateway),
                'count' => $row->count,
                'total' => number_format($row->total, 2),
            ])
            ->toArray();

        $pendingWithdrawals = Transaction::where('type', 'withdrawal')
            ->where('status', 'pending')
            ->count();

        $pendingAmount = Transaction::where('type', 'withdrawal')
            ->where('status', 'pending')
            ->sum('amount');

        $failedTransactions = Transaction::where('status', 'failed')
            ->where('created_at', '>=', $startDate)
            ->count();

        $this->paymentReport = [
            'deposits_by_gateway' => $depositsByGateway,
            'pending_withdrawals' => $pendingWithdrawals,
            'pending_amount' => number_format($pendingAmount, 2),
            'failed_transactions' => $failedTransactions,
        ];
    }
}
