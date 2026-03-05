<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProvablyFairSeed extends Model
{
    protected $fillable = [
        'user_id',
        'server_seed',
        'server_seed_hash',
        'client_seed',
        'nonce',
        'is_active',
        'revealed_at',
    ];

    protected function casts(): array
    {
        return [
            'nonce' => 'integer',
            'is_active' => 'boolean',
            'revealed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Generate a new server seed.
     */
    public static function generateServerSeed(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Hash a server seed for public display.
     */
    public static function hashServerSeed(string $serverSeed): string
    {
        return hash('sha256', $serverSeed);
    }
}
