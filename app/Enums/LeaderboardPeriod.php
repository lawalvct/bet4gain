<?php

namespace App\Enums;

enum LeaderboardPeriod: string
{
    case Daily = 'daily';
    case Weekly = 'weekly';
    case Monthly = 'monthly';
    case AllTime = 'alltime';

    public function label(): string
    {
        return match ($this) {
            self::Daily => 'Today',
            self::Weekly => 'This Week',
            self::Monthly => 'This Month',
            self::AllTime => 'All Time',
        };
    }
}
