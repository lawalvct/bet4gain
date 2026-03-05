<?php

namespace App\Models;

use App\Enums\BetStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bet extends Model
{
    protected $fillable = [
        'user_id',
        'game_round_id',
        'amount',
        'currency',
        'auto_cashout_at',
        'cashed_out_at',
        'payout',
        'is_auto',
        'status',
        'bet_slot',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:4',
            'auto_cashout_at' => 'decimal:4',
            'cashed_out_at' => 'decimal:4',
            'payout' => 'decimal:4',
            'is_auto' => 'boolean',
            'status' => BetStatus::class,
            'bet_slot' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function gameRound(): BelongsTo
    {
        return $this->belongsTo(GameRound::class);
    }

    public function isActive(): bool
    {
        return $this->status === BetStatus::Active;
    }

    public function isPending(): bool
    {
        return $this->status === BetStatus::Pending;
    }

    public function isWon(): bool
    {
        return $this->status === BetStatus::Won;
    }

    public function isDemo(): bool
    {
        return $this->currency === 'DEMO';
    }

    /**
     * Calculate profit for display.
     */
    public function getProfitAttribute(): ?float
    {
        if ($this->payout === null) {
            return null;
        }
        return $this->payout - $this->amount;
    }
}
