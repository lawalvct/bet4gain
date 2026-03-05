<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();

        // Security headers on all responses
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        // Sanitize input on all requests
        $middleware->append(\App\Http\Middleware\SanitizeInput::class);

        // Check banned/self-excluded/blacklisted IP on web + API
        $middleware->appendToGroup('web', \App\Http\Middleware\CheckBanned::class);
        $middleware->appendToGroup('api', \App\Http\Middleware\CheckBanned::class);

        // Update last_seen_at for authenticated users on web + API requests
        $middleware->appendToGroup('web', \App\Http\Middleware\UpdateLastSeen::class);
        $middleware->appendToGroup('api', \App\Http\Middleware\UpdateLastSeen::class);

        // Track login attempts
        $middleware->appendToGroup('web', \App\Http\Middleware\TrackLoginAttempt::class);

        // Rate limiting alias for routes
        $middleware->alias([
            'throttle.bet'        => \Illuminate\Routing\Middleware\ThrottleRequests::class . ':bet-placement',
            'throttle.cashout'    => \Illuminate\Routing\Middleware\ThrottleRequests::class . ':cashout',
            'throttle.chat'       => \Illuminate\Routing\Middleware\ThrottleRequests::class . ':chat',
            'throttle.deposit'    => \Illuminate\Routing\Middleware\ThrottleRequests::class . ':deposit',
            'throttle.withdrawal' => \Illuminate\Routing\Middleware\ThrottleRequests::class . ':withdrawal',
            'throttle.webhook'    => \Illuminate\Routing\Middleware\ThrottleRequests::class . ':webhook',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
