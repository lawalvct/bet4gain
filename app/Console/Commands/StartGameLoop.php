<?php

namespace App\Console\Commands;

use App\Services\GameEngine;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Artisan command to start the continuous game loop.
 *
 * Usage:
 *   php artisan game:start
 *
 * This command runs rounds in a tight loop directly (no queue needed for dev).
 * In production, use the queue worker with:
 *   php artisan queue:work --queue=game
 *
 * The loop can be stopped with Ctrl+C or by sending SIGTERM.
 */
class StartGameLoop extends Command
{
    protected $signature   = 'game:start {--once : Run a single round then exit}';
    protected $description = 'Start the continuous crash game loop';

    private bool $running = true;

    public function handle(): int
    {
        $this->info('🚀 Bet4Gain Game Engine starting...');

        // Handle graceful shutdown signals
        if (extension_loaded('pcntl')) {
            pcntl_signal(SIGTERM, fn() => $this->running = false);
            pcntl_signal(SIGINT,  fn() => $this->running = false);
        }

        $roundNumber = 1;

        while ($this->running) {
            $this->info("--- Round #{$roundNumber} ---");

            try {
                $this->runOneRound();
            } catch (\Throwable $e) {
                $this->error('Round error: ' . $e->getMessage());
                Log::error('[game:start] Round error: ' . $e->getMessage());
                sleep(3); // brief pause before retry
            }

            if ($this->option('once')) {
                break;
            }

            $roundNumber++;

            if (extension_loaded('pcntl')) {
                pcntl_signal_dispatch();
            }
        }

        $this->info('Game loop stopped.');
        return Command::SUCCESS;
    }

    private function runOneRound(): void
    {
        $bettingDuration = config('game.betting_duration', 10);
        $waitingDuration = config('game.waiting_duration', 5);

        // Create round
        $round = GameEngine::createRound();
        $this->info("  Created round #{$round->id}");

        // Waiting countdown
        for ($i = $waitingDuration; $i >= 1; $i--) {
            broadcast(new \App\Events\GameCountdown(secondsLeft: $i));
            $this->line("  Waiting: {$i}s");
            sleep(1);
        }

        // Betting phase
        GameEngine::startBetting($round);
        $this->info("  BETTING started ({$bettingDuration}s)");
        sleep($bettingDuration);

        // Running phase
        GameEngine::startRound($round);
        $this->info("  RUNNING...");

        GameEngine::runUntilCrash($round, function (float $multiplier) {
            // No per-tick console output in production.
        });

        // Crash
        GameEngine::crashRound($round);
        $this->warn("  CRASHED at {$round->crash_point}x");
    }
}
