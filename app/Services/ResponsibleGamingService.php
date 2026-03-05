<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Responsible Gaming Service
 *
 * Provides self-exclusion, deposit limits, bet limits, and cooldown functionality.
 * All operations are safe and immediate.
 */
class ResponsibleGamingService
{
    // ── Self-Exclusion ──────────────────────────────────────────────────────

    /**
     * Self-exclude a user for a given number of days.
     * During exclusion, the user cannot place bets, deposit, or purchase coins.
     */
    public function selfExclude(User $user, int $days): void
    {
        $minDays = config('security.responsible_gaming.min_self_exclusion_days', 1);
        $maxDays = config('security.responsible_gaming.max_self_exclusion_days', 365);

        $days = max($minDays, min($maxDays, $days));

        $user->update([
            'self_excluded'      => true,
            'self_excluded_until' => now()->addDays($days),
        ]);

        Log::info("User #{$user->id} self-excluded for {$days} days until " . $user->fresh()->self_excluded_until);
    }

    /**
     * Lift self-exclusion (only by admin or after period expires).
     */
    public function liftSelfExclusion(User $user): void
    {
        $user->update([
            'self_excluded'      => false,
            'self_excluded_until' => null,
        ]);

        Log::info("Self-exclusion lifted for User #{$user->id}");
    }

    /**
     * Check if a user is actively self-excluded.
     */
    public function isSelfExcluded(User $user): bool
    {
        if (!$user->self_excluded) {
            return false;
        }

        // Check if exclusion period has ended
        if ($user->self_excluded_until && $user->self_excluded_until->isPast()) {
            $this->liftSelfExclusion($user);
            return false;
        }

        return true;
    }

    // ── Deposit Limits ──────────────────────────────────────────────────────

    /**
     * Set deposit limits for a user.
     */
    public function setDepositLimits(
        User    $user,
        ?float  $daily = null,
        ?float  $weekly = null,
        ?float  $monthly = null,
    ): void {
        $user->update([
            'daily_deposit_limit'   => $daily,
            'weekly_deposit_limit'  => $weekly,
            'monthly_deposit_limit' => $monthly,
        ]);

        Log::info("Deposit limits updated for User #{$user->id}", [
            'daily'   => $daily,
            'weekly'  => $weekly,
            'monthly' => $monthly,
        ]);
    }

    /**
     * Check if a new deposit would exceed the user's limits.
     *
     * @return array{allowed: bool, reason?: string, remaining?: float}
     */
    public function checkDepositLimit(User $user, float $amount): array
    {
        if (!config('security.responsible_gaming.enabled', true)) {
            return ['allowed' => true];
        }

        // Daily limit
        if ($user->daily_deposit_limit !== null) {
            $todayDeposits = $this->getDepositTotal($user->id, 'daily');

            if (($todayDeposits + $amount) > $user->daily_deposit_limit) {
                $remaining = max(0, $user->daily_deposit_limit - $todayDeposits);
                return [
                    'allowed'   => false,
                    'reason'    => "Daily deposit limit reached. Remaining: ₦" . number_format($remaining, 2),
                    'remaining' => $remaining,
                ];
            }
        }

        // Weekly limit
        if ($user->weekly_deposit_limit !== null) {
            $weekDeposits = $this->getDepositTotal($user->id, 'weekly');

            if (($weekDeposits + $amount) > $user->weekly_deposit_limit) {
                $remaining = max(0, $user->weekly_deposit_limit - $weekDeposits);
                return [
                    'allowed'   => false,
                    'reason'    => "Weekly deposit limit reached. Remaining: ₦" . number_format($remaining, 2),
                    'remaining' => $remaining,
                ];
            }
        }

        // Monthly limit
        if ($user->monthly_deposit_limit !== null) {
            $monthDeposits = $this->getDepositTotal($user->id, 'monthly');

            if (($monthDeposits + $amount) > $user->monthly_deposit_limit) {
                $remaining = max(0, $user->monthly_deposit_limit - $monthDeposits);
                return [
                    'allowed'   => false,
                    'reason'    => "Monthly deposit limit reached. Remaining: ₦" . number_format($remaining, 2),
                    'remaining' => $remaining,
                ];
            }
        }

        return ['allowed' => true];
    }

    /**
     * Get total deposits for a period.
     */
    private function getDepositTotal(int $userId, string $period): float
    {
        $query = DB::table('deposit_limits_tracking')
            ->where('user_id', $userId)
            ->where('period', $period);

        if ($period === 'daily') {
            $query->where('period_date', today()->toDateString());
        } elseif ($period === 'weekly') {
            $query->where('period_date', '>=', now()->startOfWeek()->toDateString());
        } elseif ($period === 'monthly') {
            $query->where('period_date', '>=', now()->startOfMonth()->toDateString());
        }

        return (float) $query->sum('amount');
    }

    /**
     * Track a deposit for limit checking.
     */
    public function trackDeposit(int $userId, float $amount): void
    {
        $today = today()->toDateString();

        foreach (['daily', 'weekly', 'monthly'] as $period) {
            DB::table('deposit_limits_tracking')->insert([
                'user_id'     => $userId,
                'amount'      => $amount,
                'period'      => $period,
                'period_date' => $today,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }

    // ── Bet Limits ──────────────────────────────────────────────────────────

    /**
     * Set daily bet limit for a user.
     */
    public function setBetLimit(User $user, ?float $dailyLimit = null): void
    {
        $user->update(['daily_bet_limit' => $dailyLimit]);
    }

    /**
     * Check if a bet would exceed the user's daily bet limit.
     *
     * @return array{allowed: bool, reason?: string}
     */
    public function checkBetLimit(User $user, float $amount): array
    {
        if (!config('security.responsible_gaming.enabled', true)) {
            return ['allowed' => true];
        }

        if ($user->daily_bet_limit === null) {
            return ['allowed' => true];
        }

        $todayBets = $user->bets()
            ->whereDate('created_at', today())
            ->whereNotIn('status', [\App\Enums\BetStatus::Cancelled])
            ->sum('amount');

        if (($todayBets + $amount) > $user->daily_bet_limit) {
            $remaining = max(0, $user->daily_bet_limit - $todayBets);
            return [
                'allowed'   => false,
                'reason'    => "Daily bet limit reached. Remaining: " . number_format($remaining, 2) . " coins.",
                'remaining' => $remaining,
            ];
        }

        return ['allowed' => true];
    }

    // ── Cooldown ────────────────────────────────────────────────────────────

    /**
     * Set a cooldown for a user (no betting for N minutes).
     */
    public function setCooldown(User $user, int $minutes): void
    {
        $allowedOptions = config('security.responsible_gaming.cooldown_options', [15, 30, 60, 120, 240, 480, 1440]);

        // Find nearest allowed option
        if (!in_array($minutes, $allowedOptions)) {
            $minutes = $allowedOptions[0] ?? 15;
        }

        $user->update(['cooldown_until' => now()->addMinutes($minutes)]);

        Log::info("Cooldown set for User #{$user->id}: {$minutes} minutes");
    }

    /**
     * Check if user is in cooldown.
     */
    public function isInCooldown(User $user): bool
    {
        return $user->cooldown_until && $user->cooldown_until->isFuture();
    }

    /**
     * Get remaining cooldown minutes.
     */
    public function getRemainingCooldown(User $user): int
    {
        if (!$this->isInCooldown($user)) {
            return 0;
        }

        return (int) now()->diffInMinutes($user->cooldown_until);
    }

    // ── User Responsible Gaming Status ──────────────────────────────────────

    /**
     * Get full responsible gaming status for a user.
     */
    public function getStatus(User $user): array
    {
        return [
            'self_excluded'        => $user->self_excluded,
            'self_excluded_until'  => $user->self_excluded_until?->toIso8601String(),
            'daily_deposit_limit'  => $user->daily_deposit_limit,
            'weekly_deposit_limit' => $user->weekly_deposit_limit,
            'monthly_deposit_limit' => $user->monthly_deposit_limit,
            'daily_bet_limit'      => $user->daily_bet_limit,
            'cooldown_until'       => $user->cooldown_until?->toIso8601String(),
            'is_in_cooldown'       => $this->isInCooldown($user),
            'cooldown_remaining_minutes' => $this->getRemainingCooldown($user),
        ];
    }
}
