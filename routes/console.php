<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
*/

// Leaderboard calculations
Schedule::command('leaderboard:calculate daily')->everyFiveMinutes();
Schedule::command('leaderboard:calculate weekly')->hourly();
Schedule::command('leaderboard:calculate monthly')->hourly();
Schedule::command('leaderboard:calculate alltime')->everyThreeHours();

// Phase 10: Security scheduled tasks
Schedule::command('security:anti-cheat-scan')->everyFourHours();
Schedule::command('security:clean-data --days=90')->daily();
