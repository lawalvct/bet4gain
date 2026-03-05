<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'ip_address',
        'user_agent',
        'browser_fingerprint',
        'successful',
        'failure_reason',
        'country',
        'city',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'successful' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Log a login attempt.
     */
    public static function record(
        ?int    $userId,
        string  $ip,
        ?string $userAgent = null,
        ?string $fingerprint = null,
        bool    $successful = true,
        ?string $failureReason = null,
    ): self {
        return self::create([
            'user_id'             => $userId,
            'ip_address'          => $ip,
            'user_agent'          => $userAgent ? substr($userAgent, 0, 500) : null,
            'browser_fingerprint' => $fingerprint,
            'successful'          => $successful,
            'failure_reason'      => $failureReason,
            'created_at'          => now(),
        ]);
    }

    /**
     * Get unique IPs used by a user (for multi-account detection).
     */
    public static function userIps(int $userId, int $days = 30): array
    {
        return self::where('user_id', $userId)
            ->where('successful', true)
            ->where('created_at', '>=', now()->subDays($days))
            ->distinct()
            ->pluck('ip_address')
            ->toArray();
    }

    /**
     * Get all users who logged in from an IP (for multi-account detection).
     */
    public static function usersFromIp(string $ip, int $days = 30): array
    {
        return self::where('ip_address', $ip)
            ->where('successful', true)
            ->where('created_at', '>=', now()->subDays($days))
            ->distinct()
            ->pluck('user_id')
            ->toArray();
    }
}
