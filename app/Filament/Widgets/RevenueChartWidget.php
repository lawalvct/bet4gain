<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use App\Models\User;
use Filament\Widgets\ChartWidget;

class RevenueChartWidget extends ChartWidget
{
    protected ?string $heading = 'Revenue & Signups (Last 30 Days)';

    protected static ?int $sort = 2;

    protected ?string $pollingInterval = '60s';

    protected ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $labels = [];
        $deposits = [];
        $withdrawals = [];
        $signups = [];

        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $labels[] = $date->format('M d');

            $deposits[] = (float) Transaction::where('type', 'deposit')
                ->where('status', 'completed')
                ->whereDate('created_at', $date)
                ->sum('amount');

            $withdrawals[] = (float) Transaction::where('type', 'withdrawal')
                ->where('status', 'completed')
                ->whereDate('created_at', $date)
                ->sum('amount');

            $signups[] = User::where('is_guest', false)
                ->whereDate('created_at', $date)
                ->count();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Deposits',
                    'data' => $deposits,
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => true,
                ],
                [
                    'label' => 'Withdrawals',
                    'data' => $withdrawals,
                    'borderColor' => '#ef4444',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'fill' => true,
                ],
                [
                    'label' => 'Signups',
                    'data' => $signups,
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                    'yAxisID' => 'y1',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'position' => 'left',
                    'title' => [
                        'display' => true,
                        'text' => 'Amount',
                    ],
                ],
                'y1' => [
                    'beginAtZero' => true,
                    'position' => 'right',
                    'grid' => ['drawOnChartArea' => false],
                    'title' => [
                        'display' => true,
                        'text' => 'Signups',
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
            ],
        ];
    }
}
