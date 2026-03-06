<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class GuestController extends Controller
{
    /**
     * Create a new guest session.
     * Generates a temporary user with demo coins only.
     */
    public function create(Request $request): JsonResponse
    {
        // If already authenticated as guest, return existing session
        if (Auth::check() && Auth::user()->is_guest) {
            $user = Auth::user()->load(['coinBalance']);
            return response()->json([
                'data' => $user->only(['id', 'username', 'is_guest']),
                'demo_balance' => $user->coinBalance?->demo_balance ?? 0,
            ]);
        }

        $guestToken = Str::random(64);
        $guestId = Str::random(6);

        $user = User::create([
            'username' => 'guest_' . $guestId,
            'email' => 'guest_' . Str::random(8) . '@guest.bet4gain.com',
            'password' => bcrypt(Str::random(32)),
            'is_guest' => true,
            'guest_token' => $guestToken,
            'registration_ip' => $request->ip(),
            'last_login_ip' => $request->ip(),
            'browser_fingerprint' => $request->header('X-Browser-Fingerprint'),
        ]);

        // Create demo coin balance only (no real wallet for guests)
        $user->coinBalance()->create([
            'balance' => 0,
            'demo_balance' => config('game.demo.initial_balance', 10000),
        ]);

        // Log the guest in with the session
        Auth::login($user);

        // Set a guest token cookie (30 days)
        $cookie = cookie('bet4gain_guest_token', $guestToken, 60 * 24 * 30);

        return response()->json([
            'data' => $user->only(['id', 'username', 'is_guest']),
            'demo_balance' => config('game.demo.initial_balance', 10000),
        ])->withCookie($cookie);
    }

    /**
     * Resume an existing guest session from cookie token.
     */
    public function resume(Request $request): JsonResponse
    {
        $token = $request->cookie('bet4gain_guest_token');

        if (!$token) {
            return response()->json(['message' => 'No guest session found'], 404);
        }

        $user = User::where('guest_token', $token)
            ->where('is_guest', true)
            ->first();

        if (!$user) {
            return response()->json(['message' => 'Guest session expired'], 404);
        }

        Auth::login($user);

        $user->updateQuietly([
            'last_login_ip' => $request->ip(),
            'browser_fingerprint' => $request->header('X-Browser-Fingerprint') ?: $user->browser_fingerprint,
        ]);

        return response()->json([
            'data' => $user->only(['id', 'username', 'is_guest']),
            'demo_balance' => $user->coinBalance?->demo_balance ?? 0,
        ]);
    }
}
