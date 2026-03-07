<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoinTransfer extends Model
{
    protected $fillable = [
        'sender_id',
        'recipient_id',
        'amount',
        'fee',
        'net_amount',
        'reference',
        'type',
        'note',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'amount'     => 'decimal:4',
            'fee'        => 'decimal:4',
            'net_amount' => 'decimal:4',
        ];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public static function generateReference(): string
    {
        return 'CTX_' . strtoupper(uniqid()) . '_' . time();
    }
}
