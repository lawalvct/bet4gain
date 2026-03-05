<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Wallet;
use App\Models\CoinBalance;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create admin user & site settings
        $this->call([
            AdminSeeder::class,
            SiteSettingsSeeder::class,
        ]);

        // Create demo users with wallets and coin balances
        User::factory(20)->create()->each(function (User $user) {
            Wallet::create([
                'user_id' => $user->id,
                'balance' => fake()->randomFloat(2, 0, 50000),
                'currency' => 'NGN',
            ]);

            CoinBalance::create([
                'user_id' => $user->id,
                'balance' => fake()->randomFloat(4, 100, 50000),
                'demo_balance' => 10000,
            ]);
        });
    }
}
