<?php

namespace Tests\Feature;

use App\Models\IpRestriction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase10SecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_captures_ip_and_browser_fingerprint(): void
    {
        $response = $this
            ->withHeader('X-Browser-Fingerprint', 'phase10-test-fingerprint')
            ->post('/register', [
                'username' => 'phase10user',
                'email' => 'phase10@example.com',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ]);

        $response->assertRedirect();

        $user = User::where('email', 'phase10@example.com')->firstOrFail();

        $this->assertSame('127.0.0.1', $user->registration_ip);
        $this->assertSame('127.0.0.1', $user->last_login_ip);
        $this->assertSame('phase10-test-fingerprint', $user->browser_fingerprint);
    }

    public function test_whitelist_only_mode_blocks_non_whitelisted_ip(): void
    {
        config()->set('security.ip_restrictions.enabled', true);
        config()->set('security.ip_restrictions.whitelist_only', true);

        $response = $this->getJson('/api/settings');

        $response
            ->assertStatus(403)
            ->assertJson([
                'message' => 'Access denied. Your network is not whitelisted.',
            ]);
    }

    public function test_whitelist_only_mode_allows_whitelisted_ip(): void
    {
        config()->set('security.ip_restrictions.enabled', true);
        config()->set('security.ip_restrictions.whitelist_only', true);

        IpRestriction::create([
            'ip_address' => '127.0.0.1',
            'type' => IpRestriction::TYPE_WHITELIST,
            'reason' => 'Local development',
        ]);

        $response = $this->getJson('/api/settings');

        $response->assertOk();
    }
}
