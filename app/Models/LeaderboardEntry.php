<?php

namespace App\Models;

use App\Enums\LeaderboardPeriod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaderboardEntry extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'period',
        'total_wagered',
        'total_won',
        'total_profit',
        'best_multiplier',
        'total_games',
        'win_count',
        'calculated_at',
    ];

    protected function casts(): array
    {
        return [
            'period' => LeaderboardPeriod::class,
            'total_wagered' => 'decimal:4',
            'total_won' => 'decimal:4',
            'total_profit' => 'decimal:4',
            'best_multiplier' => 'decimal:4',
            'total_games' => 'integer',
            'win_count' => 'integer',
            'calculated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get win rate as a percentage.
     */
    public function getWinRateAttribute(): float
    {
        if ($this->total_games === 0) return 0;
        return round(($this->win_count / $this->total_games) * 100, 1);
    }
}
