<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class SiteSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // General
            ['key' => 'site_name', 'value' => 'Bet4Gain', 'type' => 'string', 'group' => 'general'],
            ['key' => 'site_description', 'value' => 'Online Multiplayer Crash Game', 'type' => 'string', 'group' => 'general'],
            ['key' => 'site_logo', 'value' => null, 'type' => 'file', 'group' => 'general'],
            ['key' => 'site_favicon', 'value' => null, 'type' => 'file', 'group' => 'general'],
            ['key' => 'maintenance_mode', 'value' => 'false', 'type' => 'boolean', 'group' => 'general'],
            ['key' => 'registration_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'general'],
            ['key' => 'guest_play_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'general'],

            // Game Settings
            ['key' => 'min_bet_amount', 'value' => '10', 'type' => 'integer', 'group' => 'game'],
            ['key' => 'max_bet_amount', 'value' => '100000', 'type' => 'integer', 'group' => 'game'],
            ['key' => 'max_payout_multiplier', 'value' => '1000', 'type' => 'integer', 'group' => 'game'],
            ['key' => 'betting_duration_seconds', 'value' => '10', 'type' => 'integer', 'group' => 'game'],
            ['key' => 'waiting_duration_seconds', 'value' => '3', 'type' => 'integer', 'group' => 'game'],
            ['key' => 'house_edge_percent', 'value' => '3', 'type' => 'integer', 'group' => 'game'],
            ['key' => 'flying_object_sprite', 'value' => 'rocket', 'type' => 'string', 'group' => 'game'],
            ['key' => 'flying_object_type', 'value' => 'rocket', 'type' => 'string', 'group' => 'game'],
            ['key' => 'background_theme', 'value' => 'starfield', 'type' => 'string', 'group' => 'game'],
            ['key' => 'demo_starting_balance', 'value' => '10000', 'type' => 'integer', 'group' => 'game'],
            ['key' => 'max_win_announcement_multiplier', 'value' => '10', 'type' => 'integer', 'group' => 'game'],

            // Payment Settings
            ['key' => 'coin_to_ngn_rate', 'value' => '1', 'type' => 'integer', 'group' => 'payment'],
            ['key' => 'ngn_to_coin_rate', 'value' => '1', 'type' => 'integer', 'group' => 'payment'],
            ['key' => 'min_deposit', 'value' => '500', 'type' => 'integer', 'group' => 'payment'],
            ['key' => 'max_deposit', 'value' => '1000000', 'type' => 'integer', 'group' => 'payment'],
            ['key' => 'min_withdrawal', 'value' => '1000', 'type' => 'integer', 'group' => 'payment'],
            ['key' => 'max_withdrawal', 'value' => '500000', 'type' => 'integer', 'group' => 'payment'],
            ['key' => 'auto_approve_withdrawal_limit', 'value' => '50000', 'type' => 'integer', 'group' => 'payment'],
            ['key' => 'withdrawal_fee_percent', 'value' => '1', 'type' => 'integer', 'group' => 'payment'],
            ['key' => 'paystack_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'payment'],
            ['key' => 'nomba_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'payment'],

            // Appearance
            ['key' => 'default_theme', 'value' => 'dark', 'type' => 'string', 'group' => 'appearance'],
            ['key' => 'primary_color', 'value' => '#f59e0b', 'type' => 'string', 'group' => 'appearance'],
            ['key' => 'accent_color', 'value' => '#8b5cf6', 'type' => 'string', 'group' => 'appearance'],

            // Chat
            ['key' => 'chat_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'general'],
            ['key' => 'chat_rate_limit_seconds', 'value' => '3', 'type' => 'integer', 'group' => 'general'],
            ['key' => 'profanity_filter_words', 'value' => '[]', 'type' => 'json', 'group' => 'general'],
        ];

        foreach ($settings as $setting) {
            SiteSetting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
