<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UpdateLastSeen
{
    /**
     * Update the authenticated user's last_seen_at timestamp.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            // Throttle DB writes — only update every 60 seconds
            $lastSeen = $request->user()->last_seen_at;
            if (!$lastSeen || $lastSeen->diffInSeconds(now()) >= 60) {
                $request->user()->updateQuietly(['last_seen_at' => now()]);
            }
        }

        return $next($request);
    }
}
