<?php

namespace App\Http\Middleware;

use App\Models\LoginLog;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Track login attempts and store IP + user agent in login_logs table.
 * Also updates user's last_login_ip field.
 */
class TrackLoginAttempt
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only track POST to login endpoint
        if (!$request->isMethod('POST') || !$this->isLoginRoute($request)) {
            return $response;
        }

        $user = $request->user();
        $successful = $response->getStatusCode() < 400;

        // On failure, try to resolve the user by their submitted email/username
        // so we can still associate the failed log entry with their account.
        if (!$user && !$successful) {
            $emailField = $request->input('email') ?? $request->input('username');
            if ($emailField) {
                $user = User::where('email', $emailField)
                    ->orWhere('username', $emailField)
                    ->first();
            }
        }

        if ($user || !$successful) {
            LoginLog::record(
                userId:        $user?->id,   // nullable — null when unknown user attempts login
                ip:            $request->ip(),
                userAgent:     $request->userAgent(),
                fingerprint:   $request->header('X-Browser-Fingerprint'),
                successful:    $successful,
                failureReason: $successful ? null : 'Invalid credentials',
            );

            if ($user && $successful) {
                $user->updateQuietly(['last_login_ip' => $request->ip()]);
            }
        }

        return $response;
    }

    private function isLoginRoute(Request $request): bool
    {
        $path = $request->path();
        return $path === 'login' || str_ends_with($path, '/login');
    }
}
