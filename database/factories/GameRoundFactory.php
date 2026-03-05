<?php

namespace Database\Factories;

use App\Models\GameRound;
use App\Enums\GameRoundStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class GameRoundFactory extends Factory
{
    protected $model = GameRound::class;

    public function definition(): array
    {
        $crashPoint = $this->generateCrashPoint();

        return [
            'round_hash' => hash('sha256', fake()->uuid()),
            'server_seed' => bin2hex(random_bytes(32)),
            'client_seed' => bin2hex(random_bytes(16)),
            'nonce' => fake()->numberBetween(1, 10000),
            'crash_point' => $crashPoint,
            'status' => GameRoundStatus::Crashed,
            'started_at' => now()->subMinutes(fake()->numberBetween(1, 1440)),
            'crashed_at' => now()->subMinutes(fake()->numberBetween(0, 1440)),
            'duration_ms' => fake()->numberBetween(1000, 60000),
        ];
    }

    private function generateCrashPoint(): float
    {
        // Simulate realistic crash point distribution
        $rand = mt_rand(1, 100);
        if ($rand <= 3) return 1.00; // 3% instant crash
        if ($rand <= 33) return round(fake()->randomFloat(2, 1.01, 1.99), 2);
        if ($rand <= 63) return round(fake()->randomFloat(2, 2.00, 4.99), 2);
        if ($rand <= 83) return round(fake()->randomFloat(2, 5.00, 9.99), 2);
        if ($rand <= 95) return round(fake()->randomFloat(2, 10.00, 49.99), 2);
        return round(fake()->randomFloat(2, 50.00, 200.00), 2);
    }
}
