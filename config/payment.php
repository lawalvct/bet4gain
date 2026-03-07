<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Payment Gateway Configuration
    |--------------------------------------------------------------------------
    */

    'default_gateway' => env('PAYMENT_DEFAULT_GATEWAY', 'paystack'),

    /*
    |--------------------------------------------------------------------------
    | Paystack Configuration
    |--------------------------------------------------------------------------
    */

    'paystack' => [
        'enabled' => env('PAYSTACK_ENABLED', true),
        'secret_key' => env('PAYSTACK_SECRET_KEY'),
        'public_key' => env('PAYSTACK_PUBLIC_KEY'),
        'base_url' => env('PAYSTACK_BASE_URL', 'https://api.paystack.co'),
        'webhook_secret' => env('PAYSTACK_WEBHOOK_SECRET'),
        'callback_url' => env('PAYSTACK_CALLBACK_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Nomba Configuration
    |--------------------------------------------------------------------------
    */

    'nomba' => [
        'enabled' => env('NOMBA_ENABLED', true),
        'client_id' => env('NOMBA_CLIENT_ID'),
        'client_secret' => env('NOMBA_CLIENT_SECRET'),
        'base_url' => env('NOMBA_BASE_URL', 'https://api.nomba.com/v1'),
        'account_id' => env('NOMBA_ACCOUNT_ID'),
        'webhook_secret' => env('NOMBA_WEBHOOK_SECRET'),
        'callback_url' => env('NOMBA_CALLBACK_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Currency & Conversion
    |--------------------------------------------------------------------------
    */

    'currency' => env('PAYMENT_CURRENCY', 'NGN'),
    'coin_to_ngn_rate' => env('COIN_TO_NGN_RATE', 1), // 1 coin = 1 NGN
    'ngn_to_coin_rate' => env('NGN_TO_COIN_RATE', 1), // 1 NGN = 1 coin

    /*
    |--------------------------------------------------------------------------
    | Limits
    |--------------------------------------------------------------------------
    */

    'min_deposit' => env('PAYMENT_MIN_DEPOSIT', 500),
    'max_deposit' => env('PAYMENT_MAX_DEPOSIT', 1000000),
    'min_withdrawal' => env('PAYMENT_MIN_WITHDRAWAL', 1000),
    'max_withdrawal' => env('PAYMENT_MAX_WITHDRAWAL', 500000),
    'auto_approve_withdrawal_limit' => env('PAYMENT_AUTO_APPROVE_LIMIT', 50000),
    'withdrawal_fee_percent' => env('PAYMENT_WITHDRAWAL_FEE', 1),

    /*
    |--------------------------------------------------------------------------
    | Coin Transfer (P2P)
    |--------------------------------------------------------------------------
    */

    'transfer_fee_percent' => env('COIN_TRANSFER_FEE_PERCENT', 2),
    'min_transfer' => env('COIN_MIN_TRANSFER', 100),
    'max_transfer' => env('COIN_MAX_TRANSFER', 100000),
    'daily_transfer_limit' => env('COIN_DAILY_TRANSFER_LIMIT', 500000),
    'min_account_age_days' => env('COIN_TRANSFER_MIN_ACCOUNT_AGE', 3),

];
