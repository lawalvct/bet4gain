<?php

namespace App\Services;

use App\Enums\BetStatus;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Bet;
use App\Models\LoginLog;
use App\Models\SuspiciousActivity;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service for detecting suspicious activity, multi-account abuse, and bot behavior.
 *
 * Runs checks and creates SuspiciousActivity flags for admin review.
 * Can be triggered by: login events, bet placement, withdrawals, scheduled checks.
 */
class AntiCheatService
{
    // ── Multi-Account Detection ─────────────────────────────────────────────

    /**
     * Check if a user shares IPs with other accounts.
     * Called after login or periodically.
     */
    public function checkMultiAccount(User $user): void
    {
        $maxUsersPerIp = config('security.multi_account.max_users_per_ip', 3);
        $lookbackDays  = config('security.multi_account.lookback_days', 30);

        // Get all IPs this user has logged in from
        $userIps = LoginLog::userIps($user->id, $lookbackDays);

        foreach ($userIps as $ip) {
            $otherUserIds = LoginLog::usersFromIp($ip, $lookbackDays);

            // Remove the current user
            $otherUserIds = array_filter($otherUserIds, fn($id) => $id !== $user->id);

            if (count($otherUserIds) >= $maxUsersPerIp) {
                $severity = count($otherUserIds) >= ($maxUsersPerIp * 2)
                    ? SuspiciousActivity::SEVERITY_HIGH
                    : SuspiciousActivity::SEVERITY_MEDIUM;

                SuspiciousActivity::flag(
                    userId:   $user->id,
                    type:     SuspiciousActivity::TYPE_MULTI_ACCOUNT,
                    severity: $severity,
                    details:  [
                        'shared_ip'       => $ip,
                        'other_user_ids'  => array_values($otherUserIds),
                        'total_users'     => count($otherUserIds) + 1,
                        'lookback_days'   => $lookbackDays,
                    ],
                );

                Log::warning("Multi-account detected: User #{$user->id} shares IP {$ip} with " . count($otherUserIds) . " other accounts.");
            }
        }
    }

    // ── Win Streak Detection ────────────────────────────────────────────────

    /**
     * Check if a user has an unusual win streak.
     */
    public function checkWinStreak(User $user): void
    {
        $threshold = config('security.suspicious.win_streak_threshold', 15);

        // Get last N bets
        $recentBets = Bet::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit($threshold + 5)
            ->pluck('status');

        $streak = 0;
        foreach ($recentBets as $status) {
            if ($status === BetStatus::Won) {
                $streak++;
            } else {
                break;
            }
        }

        if ($streak >= $threshold) {
            SuspiciousActivity::flag(
                userId:   $user->id,
                type:     SuspiciousActivity::TYPE_WIN_STREAK,
                severity: $streak >= ($threshold * 2)
                    ? SuspiciousActivity::SEVERITY_CRITICAL
                    : SuspiciousActivity::SEVERITY_HIGH,
                details:  [
                    'consecutive_wins' => $streak,
                    'threshold'        => $threshold,
                ],
            );

            Log::warning("Win streak alert: User #{$user->id} has {$streak} consecutive wins.");
        }
    }

    // ── Rapid Withdrawal Detection ──────────────────────────────────────────

    /**
     * Check for rapid/large withdrawals.
     */
    public function checkRapidWithdrawal(User $user): void
    {
        $amountThreshold = config('security.suspicious.rapid_withdrawal_amount', 500000);
        $windowMinutes   = config('security.suspicious.rapid_withdrawal_window_minutes', 30);

        $recentWithdrawals = Transaction::where('user_id', $user->id)
            ->where('type', TransactionType::Withdrawal)
            ->where('status', TransactionStatus::Pending)
            ->where('created_at', '>=', now()->subMinutes($windowMinutes))
            ->sum('amount');

        if ($recentWithdrawals >= $amountThreshold) {
            SuspiciousActivity::flag(
                userId:   $user->id,
                type:     SuspiciousActivity::TYPE_RAPID_WITHDRAWAL,
                severity: SuspiciousActivity::SEVERITY_HIGH,
                details:  [
                    'total_withdrawals'  => $recentWithdrawals,
                    'window_minutes'     => $windowMinutes,
                    'amount_threshold'   => $amountThreshold,
                ],
            );

            Log::warning("Rapid withdrawal alert: User #{$user->id} withdrew ₦{$recentWithdrawals} in {$windowMinutes} minutes.");
        }
    }

    // ── Deposit Spike Detection ─────────────────────────────────────────────

    /**
     * Check for unusual deposit spikes.
     */
    public function checkDepositSpike(User $user, float $newDepositAmount): void
    {
        $multiplier = config('security.suspicious.deposit_spike_multiplier', 10);

        $avgDeposit = Transaction::where('user_id', $user->id)
            ->where('type', TransactionType::Deposit)
            ->where('status', TransactionStatus::Completed)
            ->where('created_at', '>=', now()->subDays(30))
            ->avg('amount') ?? 0;

        if ($avgDeposit > 0 && $newDepositAmount >= $avgDeposit * $multiplier) {
            SuspiciousActivity::flag(
                userId:   $user->id,
                type:     SuspiciousActivity::TYPE_DEPOSIT_SPIKE,
                severity: SuspiciousActivity::SEVERITY_MEDIUM,
                details:  [
                    'new_deposit'    => $newDepositAmount,
                    'avg_deposit'    => round($avgDeposit, 2),
                    'multiplier'     => round($newDepositAmount / $avgDeposit, 1),
                    'threshold'      => $multiplier,
                ],
            );
        }
    }

    // ── Bot Behavior Detection ──────────────────────────────────────────────

    /**
     * Check for bot-like betting patterns.
     * Analyzes: timing regularity, consistent amounts, mechanical cashout patterns.
     */
    public function checkBotBehavior(User $user): void
    {
        // Check cashout pattern consistency
        $this->checkCashoutPattern($user);

        // Check bet timing regularity
        $this->checkBetTimingRegularity($user);
    }

    /**
     * Detect if user always cashes out at the same multiplier (bot indicator).
     */
    private function checkCashoutPattern(User $user): void
    {
        $threshold = config('security.suspicious.cashout_pattern_threshold', 20);

        $cashouts = Bet::where('user_id', $user->id)
            ->where('status', BetStatus::Won)
            ->whereNotNull('cashed_out_at')
            ->orderByDesc('created_at')
            ->limit($threshold + 5)
            ->pluck('cashed_out_at')
            ->map(fn($v) => round((float) $v, 2))
            ->toArray();

        if (count($cashouts) < $threshold) {
            return;
        }

        // Check if most cashouts are at the exact same multiplier
        $valueCounts = array_count_values($cashouts);
        $maxCount    = max($valueCounts);
        $dominantValue = array_search($maxCount, $valueCounts);

        if ($maxCount >= $threshold) {
            SuspiciousActivity::flag(
                userId:   $user->id,
                type:     SuspiciousActivity::TYPE_CASHOUT_PATTERN,
                severity: SuspiciousActivity::SEVERITY_MEDIUM,
                details:  [
                    'dominant_cashout' => $dominantValue,
                    'count'            => $maxCount,
                    'total_analyzed'   => count($cashouts),
                    'pattern_pct'      => round(($maxCount / count($cashouts)) * 100, 1),
                ],
            );
        }
    }

    /**
     * Detect bot-like bet timing (placing bets at exact intervals).
     */
    private function checkBetTimingRegularity(User $user): void
    {
        $minIntervalMs = config('security.suspicious.min_bet_interval_ms', 200);

        $recentBets = Bet::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->pluck('created_at')
            ->toArray();

        if (count($recentBets) < 10) {
            return;
        }

        // Calculate intervals
        $intervals = [];
        for ($i = 0; $i < count($recentBets) - 1; $i++) {
            $diff = $recentBets[$i]->diffInMilliseconds($recentBets[$i + 1]);
            $intervals[] = $diff;
        }

        // Check if many intervals are suspiciously fast
        $fastBets = count(array_filter($intervals, fn($ms) => $ms < $minIntervalMs));

        if ($fastBets >= 5) {
            SuspiciousActivity::flag(
                userId:   $user->id,
                type:     SuspiciousActivity::TYPE_BOT_BEHAVIOR,
                severity: SuspiciousActivity::SEVERITY_HIGH,
                details:  [
                    'fast_intervals'     => $fastBets,
                    'total_intervals'    => count($intervals),
                    'min_interval_ms'    => min($intervals),
                    'avg_interval_ms'    => round(array_sum($intervals) / count($intervals)),
                    'threshold_ms'       => $minIntervalMs,
                ],
            );

            Log::warning("Bot behavior detected: User #{$user->id} has {$fastBets} sub-{$minIntervalMs}ms bet intervals.");
        }

        // Check timing regularity (coefficient of variation < 0.1 = very regular = bot-like)
        if (count($intervals) >= 5) {
            $mean = array_sum($intervals) / count($intervals);
            if ($mean > 0) {
                $variance = array_sum(array_map(fn($v) => pow($v - $mean, 2), $intervals)) / count($intervals);
                $stddev   = sqrt($variance);
                $cv       = $stddev / $mean;

                if ($cv < 0.05 && $mean < 5000) {
                    SuspiciousActivity::flag(
                        userId:   $user->id,
                        type:     SuspiciousActivity::TYPE_BOT_BEHAVIOR,
                        severity: SuspiciousActivity::SEVERITY_CRITICAL,
                        details:  [
                            'type'              => 'timing_regularity',
                            'mean_interval_ms'  => round($mean),
                            'coeff_variation'   => round($cv, 4),
                            'assessment'        => 'Extremely regular timing suggests automated betting',
                        ],
                    );
                }
            }
        }
    }

    // ── IP Change Detection ─────────────────────────────────────────────────

    /**
     * Detect rapid IP changes (potential VPN rotation / evasion).
     */
    public function checkIpChange(User $user, string $currentIp): void
    {
        $recentLogins = LoginLog::where('user_id', $user->id)
            ->where('successful', true)
            ->where('created_at', '>=', now()->subHours(24))
            ->orderByDesc('created_at')
            ->pluck('ip_address')
            ->unique()
            ->toArray();

        // Flag if more than 5 different IPs in 24 hours
        if (count($recentLogins) > 5) {
            SuspiciousActivity::flag(
                userId:   $user->id,
                type:     SuspiciousActivity::TYPE_IP_CHANGE,
                severity: SuspiciousActivity::SEVERITY_LOW,
                details:  [
                    'unique_ips_24h' => count($recentLogins),
                    'ips'            => array_slice($recentLogins, 0, 10), // Limit stored IPs
                    'current_ip'     => $currentIp,
                ],
            );
        }
    }

    // ── Scheduled Full Scan ─────────────────────────────────────────────────

    /**
     * Run all anti-cheat checks for active users (for scheduled task).
     */
    public function runFullScan(): array
    {
        $results = [
            'users_scanned'    => 0,
            'flags_created'    => 0,
            'multi_accounts'   => 0,
            'win_streaks'      => 0,
            'bot_behaviors'    => 0,
        ];

        $initialFlagCount = SuspiciousActivity::where('reviewed', false)->count();

        // Get users who were active in last 7 days
        $activeUsers = User::where('last_seen_at', '>=', now()->subDays(7))
            ->where('is_guest', false)
            ->where('is_banned', false)
            ->cursor();

        foreach ($activeUsers as $user) {
            $results['users_scanned']++;

            try {
                $this->checkMultiAccount($user);
                $this->checkWinStreak($user);
                $this->checkBotBehavior($user);
            } catch (\Throwable $e) {
                Log::error("Anti-cheat scan failed for user #{$user->id}: " . $e->getMessage());
            }
        }

        $results['flags_created'] = SuspiciousActivity::where('reviewed', false)->count() - $initialFlagCount;

        Log::info('Anti-cheat full scan completed', $results);

        return $results;
    }
}
