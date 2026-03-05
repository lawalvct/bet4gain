<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IpRestriction extends Model
{
    protected $fillable = [
        'ip_address',
        'type',
        'reason',
        'created_by',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    public const TYPE_WHITELIST = 'whitelist';
    public const TYPE_BLACKLIST = 'blacklist';

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if an IP is blacklisted.
     */
    public static function isBlacklisted(string $ip): bool
    {
        return self::where('ip_address', $ip)
            ->where('type', self::TYPE_BLACKLIST)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    /**
     * Check if an IP is whitelisted.
     */
    public static function isWhitelisted(string $ip): bool
    {
        return self::where('ip_address', $ip)
            ->where('type', self::TYPE_WHITELIST)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    /**
     * Blacklist an IP address.
     */
    public static function blacklist(string $ip, ?string $reason = null, ?int $createdBy = null, ?\DateTimeInterface $expiresAt = null): self
    {
        return self::updateOrCreate(
            ['ip_address' => $ip, 'type' => self::TYPE_BLACKLIST],
            [
                'reason'     => $reason,
                'created_by' => $createdBy,
                'expires_at' => $expiresAt,
            ]
        );
    }
}
