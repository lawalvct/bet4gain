<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     * If a guest user is currently logged in, convert them to a registered user.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'username' => [
                'required',
                'string',
                'min:3',
                'max:20',
                'regex:/^[a-zA-Z0-9_]+$/',
                Rule::unique(User::class)->ignore(
                    Auth::check() && Auth::user()->is_guest ? Auth::id() : null
                ),
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore(
                    Auth::check() && Auth::user()->is_guest ? Auth::id() : null
                ),
            ],
            'password' => $this->passwordRules(),
        ], [
            'username.regex' => 'Username may only contain letters, numbers, and underscores.',
        ])->validate();

        // Check if we're converting a guest user
        if (Auth::check() && Auth::user()->is_guest) {
            return $this->convertGuestToRegistered(Auth::user(), $input);
        }

        // Create fresh user
        $user = User::create([
            'username' => $input['username'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
            'registration_ip' => request()->ip(),
            'last_login_ip' => request()->ip(),
            'browser_fingerprint' => request()->header('X-Browser-Fingerprint'),
        ]);

        // Create wallet
        $user->wallet()->create([
            'balance' => 0,
            'currency' => 'NGN',
        ]);

        // Create coin balance with demo coins
        $user->coinBalance()->create([
            'balance' => 0,
            'demo_balance' => config('game.demo.initial_balance', 10000),
        ]);

        return $user;
    }

    /**
     * Convert an existing guest user to a fully registered user.
     * Preserves their betting history, stats, and demo balance.
     */
    protected function convertGuestToRegistered(User $guest, array $input): User
    {
        $guest->update([
            'username' => $input['username'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
            'is_guest' => false,
            'guest_token' => null,
            'registration_ip' => $guest->registration_ip ?: request()->ip(),
            'last_login_ip' => request()->ip(),
            'browser_fingerprint' => request()->header('X-Browser-Fingerprint') ?: $guest->browser_fingerprint,
        ]);

        // Create wallet if guest doesn't have one
        if (!$guest->wallet) {
            $guest->wallet()->create([
                'balance' => 0,
                'currency' => 'NGN',
            ]);
        }

        // Ensure coin balance exists (guest should already have one)
        if (!$guest->coinBalance) {
            $guest->coinBalance()->create([
                'balance' => 0,
                'demo_balance' => config('game.demo.initial_balance', 10000),
            ]);
        }

        // Clear the guest cookie
        cookie()->queue(cookie()->forget('bet4gain_guest_token'));

        return $guest->fresh();
    }
}
