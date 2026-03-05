<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutoBetConfig extends Model
{
    protected $fillable = [
        'user_id',
        'bet_amount',
        'auto_cashout_at',
        'stop_on_loss',
        'stop_on_profit',
        'increase_on_loss_percent',
        'increase_on_win_percent',
        'max_rounds',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'bet_amount' => 'decimal:4',
            'auto_cashout_at' => 'decimal:4',
            'stop_on_loss' => 'decimal:4',
            'stop_on_profit' => 'decimal:4',
            'increase_on_loss_percent' => 'decimal:2',
            'increase_on_win_percent' => 'decimal:2',
            'max_rounds' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
