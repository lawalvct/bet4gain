<?php

namespace App\Http\Middleware;

use App\Models\IpRestriction;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Check if user is banned or self-excluded, and enforce IP restrictions.
 * Applied globally to web/api middleware groups.
 */
class CheckBanned
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // ── Check user ban ──────────────────────────────────
        if ($user && $user->is_banned) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Your account has been suspended. Contact support for more information.',
                ], 403);
            }

            auth()->logout();
            $request->session()->invalidate();

            return redirect()->route('game')->with('error', 'Your account has been suspended.');
        }

        // ── Check self-exclusion ────────────────────────────
        if ($user && $user->self_excluded) {
            // Check if exclusion period has ended
            if ($user->self_excluded_until && $user->self_excluded_until->isPast()) {
                $user->update([
                    'self_excluded'       => false,
                    'self_excluded_until'  => null,
                ]);
            } else {
                // Block game-related API calls during self-exclusion
                if ($this->isGameEndpoint($request)) {
                    $until = $user->self_excluded_until
                        ? $user->self_excluded_until->format('M d, Y H:i')
                        : 'indefinitely';

                    return response()->json([
                        'message' => "You are self-excluded until {$until}. This action is not available.",
                    ], 403);
                }
            }
        }

        // ── Check cooldown ──────────────────────────────────
        if ($user && $user->cooldown_until && $user->cooldown_until->isFuture()) {
            if ($this->isBettingEndpoint($request)) {
                $remaining = now()->diffInMinutes($user->cooldown_until);
                return response()->json([
                    'message' => "You are in a cool-down period. {$remaining} minute(s) remaining.",
                ], 403);
            }
        }

        // ── Check IP blacklist ──────────────────────────────
        if (config('security.ip_restrictions.enabled', true)) {
            $ip = $request->ip();

            if (IpRestriction::isBlacklisted($ip)) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Access denied.'], 403);
                }
                abort(403, 'Access denied.');
            }
        }

        return $next($request);
    }

    /**
     * Check if this is a game/betting related endpoint.
     */
    private function isGameEndpoint(Request $request): bool
    {
        $path = $request->path();
        return str_starts_with($path, 'api/game/bet')
            || str_starts_with($path, 'api/game/cashout')
            || str_starts_with($path, 'api/wallet/deposit')
            || str_starts_with($path, 'api/wallet/purchase-coins');
    }

    /**
     * Check if this is a betting endpoint.
     */
    private function isBettingEndpoint(Request $request): bool
    {
        $path = $request->path();
        return str_starts_with($path, 'api/game/bet')
            || str_starts_with($path, 'api/game/cashout');
    }
}
