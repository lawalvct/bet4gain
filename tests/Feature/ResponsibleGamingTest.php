<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ResponsibleGamingTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_set_cooldown_and_read_status(): void
    {
        Carbon::setTestNow(now());

        $user = User::factory()->create([
            'role' => UserRole::User,
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/responsible-gaming/cooldown', [
            'minutes' => 60,
        ])
            ->assertOk()
            ->assertJsonPath('data.is_in_cooldown', true)
            ->assertJsonPath('data.cooldown_remaining_minutes', 60);

        $this->getJson('/api/responsible-gaming/status')
            ->assertOk()
            ->assertJsonPath('data.is_in_cooldown', true);

        Carbon::setTestNow();
    }

    public function test_authenticated_user_can_self_exclude(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/responsible-gaming/self-exclude', [
            'days' => 7,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.self_excluded', true);

        $this->assertTrue($user->fresh()->self_excluded);
    }

    public function test_unauthenticated_user_cannot_access_responsible_gaming_routes(): void
    {
        $this->getJson('/api/responsible-gaming/status')->assertUnauthorized();
    }
}
