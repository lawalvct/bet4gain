<?php

namespace Tests\Feature;

use App\Models\Advertisement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_ads_endpoint_returns_matching_active_ad_for_requested_placement(): void
    {
        Advertisement::create([
            'title' => 'Sidebar Ad',
            'image' => 'advertisements/sidebar.png',
            'url' => 'https://example.com/sidebar',
            'placement' => 'sidebar',
            'is_active' => true,
            'priority' => 10,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHour(),
        ]);

        Advertisement::create([
            'title' => 'Banner Ad',
            'image' => 'advertisements/banner.png',
            'url' => 'https://example.com/banner',
            'placement' => 'banner',
            'is_active' => true,
            'priority' => 1,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHour(),
        ]);

        $response = $this->getJson('/api/ads/sidebar');

        $response
            ->assertOk()
            ->assertJsonPath('data.title', 'Sidebar Ad')
            ->assertJsonPath('data.target_url', 'https://example.com/sidebar')
            ->assertJsonPath('data.placement', 'sidebar');

        $this->assertStringContainsString('/storage/advertisements/sidebar.png', $response->json('data.image_url'));
    }

    public function test_ads_endpoint_falls_back_to_highest_priority_active_ad(): void
    {
        Advertisement::create([
            'title' => 'Low Priority Banner',
            'image' => 'advertisements/low.png',
            'url' => 'https://example.com/low',
            'placement' => 'banner',
            'is_active' => true,
            'priority' => 1,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHour(),
        ]);

        Advertisement::create([
            'title' => 'High Priority Banner',
            'image' => 'advertisements/high.png',
            'url' => 'https://example.com/high',
            'placement' => 'banner',
            'is_active' => true,
            'priority' => 50,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHour(),
        ]);

        $response = $this->getJson('/api/ads/between-rounds');

        $response
            ->assertOk()
            ->assertJsonPath('data.title', 'High Priority Banner')
            ->assertJsonPath('data.placement', 'banner');
    }

    public function test_public_ad_tracking_endpoints_increment_counts(): void
    {
        $ad = Advertisement::create([
            'title' => 'Trackable Ad',
            'image' => 'advertisements/trackable.png',
            'url' => 'https://example.com/track',
            'placement' => 'banner',
            'is_active' => true,
            'priority' => 5,
        ]);

        $this->postJson("/api/ads/{$ad->id}/impression")
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->postJson("/api/ads/{$ad->id}/click")
            ->assertOk()
            ->assertJson(['ok' => true]);

        $ad->refresh();

        $this->assertSame(1, $ad->impressions);
        $this->assertSame(1, $ad->clicks);
    }
}
