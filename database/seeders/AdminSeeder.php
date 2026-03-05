<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\Wallet;
use App\Models\CoinBalance;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::create([
            'username' => 'admin',
            'email' => 'admin@bet4gain.com',
            'password' => Hash::make('password'),
            'role' => UserRole::Admin,
            'email_verified_at' => now(),
            'settings' => ['theme' => 'dark', 'sound' => true],
        ]);

        Wallet::create([
            'user_id' => $admin->id,
            'balance' => 0,
            'currency' => 'NGN',
        ]);

        CoinBalance::create([
            'user_id' => $admin->id,
            'balance' => 100000,
            'demo_balance' => 10000,
        ]);
    }
}
