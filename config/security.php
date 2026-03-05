<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Security & Anti-Cheat Configuration
    |--------------------------------------------------------------------------
    */

    // ── Rate Limiting ───────────────────────────────────────────────────────

    'rate_limits' => [
        // Bets: max 5 per 10 seconds per user
        'bet_placement' => [
            'max_attempts' => env('RATE_LIMIT_BET_MAX', 5),
            'decay_seconds' => env('RATE_LIMIT_BET_DECAY', 10),
        ],

        // Cashout: max 5 per 10 seconds per user
        'cashout' => [
            'max_attempts' => env('RATE_LIMIT_CASHOUT_MAX', 5),
            'decay_seconds' => env('RATE_LIMIT_CASHOUT_DECAY', 10),
        ],

        // Chat: 1 message per 3 seconds (already in ChatService, this is backup)
        'chat' => [
            'max_attempts' => env('RATE_LIMIT_CHAT_MAX', 1),
            'decay_seconds' => env('RATE_LIMIT_CHAT_DECAY', 3),
        ],

        // Deposits: max 5 per minute
        'deposit' => [
            'max_attempts' => env('RATE_LIMIT_DEPOSIT_MAX', 5),
            'decay_seconds' => env('RATE_LIMIT_DEPOSIT_DECAY', 60),
        ],

        // Withdrawals: max 3 per 10 minutes
        'withdrawal' => [
            'max_attempts' => env('RATE_LIMIT_WITHDRAWAL_MAX', 3),
            'decay_seconds' => env('RATE_LIMIT_WITHDRAWAL_DECAY', 600),
        ],

        // Registration: max 3 per 10 minutes per IP
        'registration' => [
            'max_attempts' => env('RATE_LIMIT_REGISTER_MAX', 3),
            'decay_seconds' => env('RATE_LIMIT_REGISTER_DECAY', 600),
        ],

        // General API: max 120 per minute per user/IP
        'api_general' => [
            'max_attempts' => env('RATE_LIMIT_API_MAX', 120),
            'decay_seconds' => env('RATE_LIMIT_API_DECAY', 60),
        ],

        // Game state polling: max 60 per minute
        'game_state' => [
            'max_attempts' => env('RATE_LIMIT_GAME_STATE_MAX', 60),
            'decay_seconds' => env('RATE_LIMIT_GAME_STATE_DECAY', 60),
        ],

        // Webhook: max 100 per minute per IP
        'webhook' => [
            'max_attempts' => env('RATE_LIMIT_WEBHOOK_MAX', 100),
            'decay_seconds' => env('RATE_LIMIT_WEBHOOK_DECAY', 60),
        ],
    ],

    // ── Multi-Account Detection ─────────────────────────────────────────────

    'multi_account' => [
        // Max users allowed from same IP before flagging
        'max_users_per_ip' => env('SECURITY_MAX_USERS_PER_IP', 3),

        // Lookback period for IP sharing analysis (days)
        'lookback_days' => env('SECURITY_IP_LOOKBACK_DAYS', 30),

        // Auto-flag users sharing IPs (true) or just log (false)
        'auto_flag' => env('SECURITY_MULTI_ACCOUNT_AUTO_FLAG', true),
    ],

    // ── Suspicious Activity Thresholds ──────────────────────────────────────

    'suspicious' => [
        // Win streak: flag if X consecutive wins
        'win_streak_threshold' => env('SECURITY_WIN_STREAK_THRESHOLD', 15),

        // Rapid withdrawal: flag if withdrawn > X NGN in Y minutes
        'rapid_withdrawal_amount' => env('SECURITY_RAPID_WITHDRAWAL_AMOUNT', 500000),
        'rapid_withdrawal_window_minutes' => env('SECURITY_RAPID_WITHDRAWAL_WINDOW', 30),

        // Deposit spike: flag if deposit > X times average
        'deposit_spike_multiplier' => env('SECURITY_DEPOSIT_SPIKE_MULTIPLIER', 10),

        // Bot behavior: flag if avg bet time < X ms
        'min_bet_interval_ms' => env('SECURITY_MIN_BET_INTERVAL_MS', 200),

        // Cashout pattern: flag if always cashing out at exact same multiplier (AI-like)
        'cashout_pattern_threshold' => env('SECURITY_CASHOUT_PATTERN_THRESHOLD', 20),
    ],

    // ── Responsible Gaming ──────────────────────────────────────────────────

    'responsible_gaming' => [
        // Enable/disable responsible gaming features
        'enabled' => env('RESPONSIBLE_GAMING_ENABLED', true),

        // Default limits (can be overridden per user)
        'default_daily_deposit_limit' => env('DEFAULT_DAILY_DEPOSIT_LIMIT', null),
        'default_weekly_deposit_limit' => env('DEFAULT_WEEKLY_DEPOSIT_LIMIT', null),
        'default_monthly_deposit_limit' => env('DEFAULT_MONTHLY_DEPOSIT_LIMIT', null),

        // Minimum self-exclusion period (days)
        'min_self_exclusion_days' => env('MIN_SELF_EXCLUSION_DAYS', 1),
        'max_self_exclusion_days' => env('MAX_SELF_EXCLUSION_DAYS', 365),

        // Cooldown options (minutes)
        'cooldown_options' => [15, 30, 60, 120, 240, 480, 1440],

        // Session time reminder (minutes) — 0 to disable
        'session_time_reminder' => env('SESSION_TIME_REMINDER', 60),
    ],

    // ── Security Headers ────────────────────────────────────────────────────

    'headers' => [
        'x_frame_options' => 'DENY',
        'x_content_type_options' => 'nosniff',
        'x_xss_protection' => '1; mode=block',
        'referrer_policy' => 'strict-origin-when-cross-origin',
        'permissions_policy' => 'camera=(), microphone=(), geolocation=()',
        'strict_transport_security' => 'max-age=31536000; includeSubDomains',
        // Content Security Policy — adjust domains as needed
        'content_security_policy' => env('CSP_POLICY', "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob: https://ui-avatars.com; connect-src 'self' ws: wss:; font-src 'self' data:; frame-ancestors 'none';"),
    ],

    // ── Encryption ──────────────────────────────────────────────────────────

    'encryption' => [
        // Fields to encrypt at rest (model => fields)
        'encrypt_fields' => [
            'Wallet' => ['balance'],
            'ProvablyFairSeed' => ['server_seed'],
        ],
    ],

    // ── IP Restrictions ─────────────────────────────────────────────────────

    'ip_restrictions' => [
        // Enable IP blacklist/whitelist checking
        'enabled' => env('IP_RESTRICTIONS_ENABLED', true),

        // Block all if not whitelisted (strict mode) — NOT recommended for production
        'whitelist_only' => env('IP_WHITELIST_ONLY', false),
    ],

];
