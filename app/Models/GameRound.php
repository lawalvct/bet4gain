<?php

namespace App\Models;

use App\Enums\GameRoundStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameRound extends Model
{
    protected $fillable = [
        'round_hash',
        'server_seed',
        'client_seed',
        'nonce',
        'crash_point',
        'status',
        'started_at',
        'crashed_at',
        'duration_ms',
    ];

    protected function casts(): array
    {
        return [
            'crash_point' => 'decimal:4',
            'status' => GameRoundStatus::class,
            'started_at' => 'datetime',
            'crashed_at' => 'datetime',
            'nonce' => 'integer',
            'duration_ms' => 'integer',
        ];
    }

    public function bets(): HasMany
    {
        return $this->hasMany(Bet::class);
    }

    public function activeBets(): HasMany
    {
        return $this->hasMany(Bet::class)->where('status', 'active');
    }

    public function isAcceptingBets(): bool
    {
        return $this->status === GameRoundStatus::Betting;
    }

    public function isRunning(): bool
    {
        return $this->status === GameRoundStatus::Running;
    }

    public function hasCrashed(): bool
    {
        return $this->status === GameRoundStatus::Crashed;
    }

    /**
     * Get the crash point color class for display.
     */
    public function getCrashPointColorAttribute(): string
    {
        if ($this->crash_point >= 10) return 'text-purple-500';
        if ($this->crash_point >= 2) return 'text-green-500';
        if ($this->crash_point >= 1.5) return 'text-yellow-500';
        return 'text-red-500';
    }
}
