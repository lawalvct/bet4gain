<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuspiciousActivity extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'severity',
        'details',
        'reviewed',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
    ];

    protected function casts(): array
    {
        return [
            'details'     => 'json',
            'reviewed'    => 'boolean',
            'reviewed_at' => 'datetime',
        ];
    }

    // ── Types ───────────────────────────────────────────────
    public const TYPE_MULTI_ACCOUNT     = 'multi_account';
    public const TYPE_WIN_STREAK        = 'win_streak';
    public const TYPE_RAPID_WITHDRAWAL  = 'rapid_withdrawal';
    public const TYPE_BOT_BEHAVIOR      = 'bot_behavior';
    public const TYPE_IP_CHANGE         = 'ip_change';
    public const TYPE_DEPOSIT_SPIKE     = 'deposit_spike';
    public const TYPE_CASHOUT_PATTERN   = 'cashout_pattern';

    // ── Severities ──────────────────────────────────────────
    public const SEVERITY_LOW      = 'low';
    public const SEVERITY_MEDIUM   = 'medium';
    public const SEVERITY_HIGH     = 'high';
    public const SEVERITY_CRITICAL = 'critical';

    // ── Relationships ───────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // ── Static Helpers ──────────────────────────────────────

    /**
     * Flag a suspicious activity.
     */
    public static function flag(
        int     $userId,
        string  $type,
        string  $severity = self::SEVERITY_MEDIUM,
        array   $details = [],
    ): self {
        // Avoid duplicate flags for same type within 24 hours
        $existing = self::where('user_id', $userId)
            ->where('type', $type)
            ->where('reviewed', false)
            ->where('created_at', '>=', now()->subDay())
            ->first();

        if ($existing) {
            // Update details with new data
            $existing->update([
                'details'  => array_merge($existing->details ?? [], ['updated' => $details]),
                'severity' => self::higherSeverity($existing->severity, $severity),
            ]);
            return $existing;
        }

        $activity = self::create([
            'user_id'  => $userId,
            'type'     => $type,
            'severity' => $severity,
            'details'  => $details,
        ]);

        // Auto-flag user for high/critical severity
        if (in_array($severity, [self::SEVERITY_HIGH, self::SEVERITY_CRITICAL])) {
            User::where('id', $userId)->update([
                'is_flagged'  => true,
                'flag_reason' => "Auto-flagged: {$type}",
                'flagged_at'  => now(),
            ]);
        }

        return $activity;
    }

    /**
     * Return the higher severity level.
     */
    private static function higherSeverity(string $a, string $b): string
    {
        $levels = [
            self::SEVERITY_LOW      => 1,
            self::SEVERITY_MEDIUM   => 2,
            self::SEVERITY_HIGH     => 3,
            self::SEVERITY_CRITICAL => 4,
        ];

        return ($levels[$b] ?? 2) > ($levels[$a] ?? 2) ? $b : $a;
    }

    /**
     * Mark as reviewed.
     */
    public function markReviewed(int $reviewerId, ?string $notes = null): void
    {
        $this->update([
            'reviewed'     => true,
            'reviewed_by'  => $reviewerId,
            'reviewed_at'  => now(),
            'review_notes' => $notes,
        ]);
    }

    /**
     * Get unreviewed count by severity (for admin dashboard).
     */
    public static function unreviewedCounts(): array
    {
        return self::where('reviewed', false)
            ->selectRaw('severity, count(*) as count')
            ->groupBy('severity')
            ->pluck('count', 'severity')
            ->toArray();
    }
}
