<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanSecurityData extends Command
{
    protected $signature = 'security:clean-data {--days=90 : Days of data to keep}';

    protected $description = 'Clean old security data: login logs, expired IP restrictions, old deposit limit tracking';

    public function handle(): int
    {
        $days = (int) $this->option('days');

        $this->info("Cleaning security data older than {$days} days...");

        // Clean old login logs
        $loginLogsCleaned = DB::table('login_logs')
            ->where('created_at', '<', now()->subDays($days))
            ->delete();
        $this->line("  Login logs cleaned: {$loginLogsCleaned}");

        // Clean expired IP restrictions
        $ipRestrictionsCleaned = DB::table('ip_restrictions')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->delete();
        $this->line("  Expired IP restrictions cleaned: {$ipRestrictionsCleaned}");

        // Clean old deposit limit tracking
        $depositTrackingCleaned = DB::table('deposit_limits_tracking')
            ->where('period_date', '<', now()->subDays(35)) // Keep 35 days for monthly limit
            ->delete();
        $this->line("  Deposit tracking records cleaned: {$depositTrackingCleaned}");

        // Clean old reviewed suspicious activities
        $suspiciousCleaned = DB::table('suspicious_activities')
            ->where('reviewed', true)
            ->where('created_at', '<', now()->subDays($days))
            ->delete();
        $this->line("  Reviewed suspicious activities cleaned: {$suspiciousCleaned}");

        $total = $loginLogsCleaned + $ipRestrictionsCleaned + $depositTrackingCleaned + $suspiciousCleaned;
        $this->info("Done. Total records cleaned: {$total}");

        return self::SUCCESS;
    }
}
