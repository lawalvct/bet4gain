<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Game Configuration
    |--------------------------------------------------------------------------
    |
    | Core game settings for the crash game engine. These are defaults
    | that can be overridden via SiteSettings (admin panel).
    |
    */

    'min_bet' => env('GAME_MIN_BET', 10),
    'max_bet' => env('GAME_MAX_BET', 100000),
    'max_payout_multiplier' => env('GAME_MAX_PAYOUT_MULTIPLIER', 1000),

    /*
    |--------------------------------------------------------------------------
    | Round Timing
    |--------------------------------------------------------------------------
    */

    'betting_duration' => env('GAME_BETTING_DURATION', 10), // seconds
    'waiting_duration' => env('GAME_WAITING_DURATION', 3),  // seconds between rounds
    'tick_rate' => env('GAME_TICK_RATE', 100), // ms between multiplier broadcasts

    /*
    |--------------------------------------------------------------------------
    | House Edge
    |--------------------------------------------------------------------------
    */

    'house_edge' => env('GAME_HOUSE_EDGE', 0.03), // 3%

    /*
    |--------------------------------------------------------------------------
    | Multiplier Growth
    |--------------------------------------------------------------------------
    |
    | The multiplier grows exponentially: multiplier = e^(growth_rate * elapsed_seconds)
    |
    */

    'growth_rate' => env('GAME_GROWTH_RATE', 0.06),

    /*
    |--------------------------------------------------------------------------
    | Demo / Guest Play
    |--------------------------------------------------------------------------
    */

    'demo_starting_balance' => env('GAME_DEMO_STARTING_BALANCE', 10000),
    'guest_play_enabled' => env('GAME_GUEST_PLAY_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Visual Settings
    |--------------------------------------------------------------------------
    */

    'flying_object' => env('GAME_FLYING_OBJECT', 'rocket'), // rocket, plane, custom
    'background_theme' => env('GAME_BACKGROUND_THEME', 'starfield'), // starfield, sky, grid

    /*
    |--------------------------------------------------------------------------
    | Chat Settings
    |--------------------------------------------------------------------------
    */

    'chat_enabled' => env('GAME_CHAT_ENABLED', true),
    'chat_rate_limit' => env('GAME_CHAT_RATE_LIMIT', 3), // seconds between messages
    'chat_max_length' => env('GAME_CHAT_MAX_LENGTH', 200),
    'chat_history_count' => env('GAME_CHAT_HISTORY_COUNT', 50),

    /*
    |--------------------------------------------------------------------------
    | Leaderboard Settings
    |--------------------------------------------------------------------------
    */

    'leaderboard_cache_ttl' => env('GAME_LEADERBOARD_CACHE_TTL', 300), // 5 minutes
    'leaderboard_top_count' => env('GAME_LEADERBOARD_TOP_COUNT', 50),

    /*
    |--------------------------------------------------------------------------
    | Win Announcements
    |--------------------------------------------------------------------------
    */

    'win_announcement_min_multiplier' => env('GAME_WIN_ANNOUNCEMENT_MIN', 10),

    /*
    |--------------------------------------------------------------------------
    | Anti-Cheat
    |--------------------------------------------------------------------------
    */

    'max_bets_per_round' => env('GAME_MAX_BETS_PER_ROUND', 2),
    'max_websocket_connections_per_user' => env('GAME_MAX_WS_CONNECTIONS', 3),

];
