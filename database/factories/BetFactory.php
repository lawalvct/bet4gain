<?php

namespace Database\Factories;

use App\Models\Bet;
use App\Models\User;
use App\Models\GameRound;
use App\Enums\BetStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class BetFactory extends Factory
{
    protected $model = Bet::class;

    public function definition(): array
    {
        $status = fake()->randomElement([BetStatus::Won, BetStatus::Lost]);
        $amount = fake()->randomElement([10, 25, 50, 100, 250, 500, 1000]);
        $cashedOutAt = $status === BetStatus::Won ? round(fake()->randomFloat(2, 1.10, 10.00), 2) : null;
        $payout = $cashedOutAt ? round($amount * $cashedOutAt, 4) : 0;

        return [
            'user_id' => User::factory(),
            'game_round_id' => GameRound::factory(),
            'amount' => $amount,
            'currency' => 'COINS',
            'auto_cashout_at' => fake()->optional(0.4)->randomFloat(2, 1.5, 5.0),
            'cashed_out_at' => $cashedOutAt,
            'payout' => $payout,
            'is_auto' => fake()->boolean(20),
            'status' => $status,
            'bet_slot' => 1,
        ];
    }

    public function won(): static
    {
        return $this->state(function (array $attributes) {
            $multiplier = round(fake()->randomFloat(2, 1.10, 10.00), 2);
            return [
                'status' => BetStatus::Won,
                'cashed_out_at' => $multiplier,
                'payout' => round($attributes['amount'] * $multiplier, 4),
            ];
        });
    }

    public function lost(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BetStatus::Lost,
            'cashed_out_at' => null,
            'payout' => 0,
        ]);
    }

    public function demo(): static
    {
        return $this->state(fn (array $attributes) => [
            'currency' => 'DEMO',
        ]);
    }
}
