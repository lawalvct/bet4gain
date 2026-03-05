<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoinBalance extends Model
{
    protected $fillable = [
        'user_id',
        'balance',
        'demo_balance',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:4',
            'demo_balance' => 'decimal:4',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hasEnough(float $amount): bool
    {
        return $this->balance >= $amount;
    }

    public function hasDemoEnough(float $amount): bool
    {
        return $this->demo_balance >= $amount;
    }
}
