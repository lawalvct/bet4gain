<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    /**
     * Redirect to the OAuth provider.
     */
    public function redirect(string $provider)
    {
        return Socialite::driver($provider)->redirect();
    }

    /**
     * Handle the OAuth callback.
     */
    public function callback(string $provider)
    {
        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Exception $e) {
            return redirect()->route('login')->with('error', 'Social login failed. Please try again.');
        }

        // Find existing user by provider or email
        $user = User::where('provider', $provider)
            ->where('provider_id', $socialUser->getId())
            ->first();

        if (!$user) {
            // Check if email already exists
            $user = User::where('email', $socialUser->getEmail())->first();

            if ($user) {
                // Link social account to existing user
                $user->update([
                    'provider' => $provider,
                    'provider_id' => $socialUser->getId(),
                    'avatar' => $socialUser->getAvatar(),
                ]);
            } else {
                // Create new user
                $user = User::create([
                    'name' => $socialUser->getName() ?? $socialUser->getNickname() ?? 'User',
                    'username' => $this->generateUsername($socialUser),
                    'email' => $socialUser->getEmail(),
                    'password' => bcrypt(Str::random(32)),
                    'provider' => $provider,
                    'provider_id' => $socialUser->getId(),
                    'avatar' => $socialUser->getAvatar(),
                    'email_verified_at' => now(),
                ]);

                // Create wallet and coin balance
                $user->wallet()->create([
                    'balance' => 0,
                    'currency' => 'NGN',
                ]);

                $user->coinBalance()->create([
                    'balance' => config('game.demo.initial_balance', 10000),
                    'demo_balance' => config('game.demo.initial_balance', 10000),
                ]);
            }
        }

        Auth::login($user, true);

        return redirect()->route('game');
    }

    /**
     * Generate a unique username from social profile.
     */
    private function generateUsername($socialUser): string
    {
        $base = $socialUser->getNickname()
            ?? Str::slug(explode('@', $socialUser->getEmail())[0])
            ?? 'user';

        $base = Str::slug($base, '_');
        $username = $base;
        $counter = 1;

        while (User::where('username', $username)->exists()) {
            $username = $base . '_' . $counter;
            $counter++;
        }

        return $username;
    }
}
