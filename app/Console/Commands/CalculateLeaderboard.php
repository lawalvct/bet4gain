<?php

namespace App\Console\Commands;

use App\Enums\LeaderboardPeriod;
use App\Services\LeaderboardService;
use Illuminate\Console\Command;

class CalculateLeaderboard extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'leaderboard:calculate
                            {period? : The period to calculate (daily, weekly, monthly, alltime). Defaults to all.}';

    /**
     * The console command description.
     */
    protected $description = 'Recalculate leaderboard entries for one or all periods';

    public function handle(LeaderboardService $service): int
    {
        $periodArg = $this->argument('period');

        if ($periodArg) {
            $period = LeaderboardPeriod::tryFrom($periodArg);

            if (!$period) {
                $this->error("Invalid period: {$periodArg}. Use: daily, weekly, monthly, alltime");
                return self::FAILURE;
            }

            $this->info("Calculating {$period->label()} leaderboard...");
            $count = $service->calculate($period);
            $this->info("Done. {$count} user(s) ranked for {$period->label()}.");
        } else {
            $this->info('Calculating all leaderboard periods...');
            $results = $service->calculateAll();

            foreach ($results as $period => $count) {
                $this->info("  {$period}: {$count} user(s)");
            }

            $this->info('All leaderboard periods calculated successfully.');
        }

        return self::SUCCESS;
    }
}
