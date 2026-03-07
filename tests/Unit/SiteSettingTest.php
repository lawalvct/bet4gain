<?php

namespace Tests\Unit;

use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SiteSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_typed_values_from_settings(): void
    {
        SiteSetting::set('test_boolean', true, 'boolean');
        SiteSetting::set('test_integer', 42, 'integer');
        SiteSetting::set('test_json', ['theme' => 'dark'], 'json');

        $this->assertTrue(SiteSetting::get('test_boolean'));
        $this->assertSame(42, SiteSetting::get('test_integer'));
        $this->assertSame(['theme' => 'dark'], SiteSetting::get('test_json'));
    }

    public function test_it_uses_cache_until_cache_is_cleared(): void
    {
        SiteSetting::set('site_name', 'Bet4Gain', 'string');

        $this->assertSame('Bet4Gain', SiteSetting::get('site_name'));

        DB::table('site_settings')
            ->where('key', 'site_name')
            ->update(['value' => 'Changed Name']);

        $this->assertSame('Bet4Gain', SiteSetting::get('site_name'));

        SiteSetting::clearCache();

        $this->assertSame('Changed Name', SiteSetting::get('site_name'));
    }
}
