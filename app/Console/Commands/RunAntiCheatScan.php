<?php

namespace App\Console\Commands;

use App\Services\AntiCheatService;
use Illuminate\Console\Command;

class RunAntiCheatScan extends Command
{
    protected $signature = 'security:anti-cheat-scan';

    protected $description = 'Run anti-cheat scan on active users (multi-account detection, win streaks, bot behavior)';

    public function handle(AntiCheatService $service): int
    {
        $this->info('Starting anti-cheat scan...');

        $results = $service->runFullScan();

        $this->info("Scan complete:");
        $this->table(
            ['Metric', 'Value'],
            collect($results)->map(fn($v, $k) => [$k, $v])->toArray()
        );

        if ($results['flags_created'] > 0) {
            $this->warn("{$results['flags_created']} new suspicious activity flag(s) created. Review in admin panel.");
        } else {
            $this->info('No new suspicious activity detected.');
        }

        return self::SUCCESS;
    }
}
