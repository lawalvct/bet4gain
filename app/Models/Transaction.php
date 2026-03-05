<?php

namespace App\Models;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'currency',
        'reference',
        'gateway',
        'gateway_reference',
        'status',
        'metadata',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:4',
            'type' => TransactionType::class,
            'status' => TransactionStatus::class,
            'metadata' => 'json',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Generate a unique transaction reference.
     */
    public static function generateReference(string $prefix = 'TXN'): string
    {
        return $prefix . '_' . strtoupper(uniqid()) . '_' . time();
    }
}
