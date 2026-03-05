<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast when a round transitions to RUNNING.
 * Client canvas animation should begin.
 */
class GameStarted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int    $roundId,
        public readonly string $serverSeedHash,
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('game');
    }

    public function broadcastAs(): string
    {
        return 'round.started';
    }

    public function broadcastWith(): array
    {
        return [
            'round_id'         => $this->roundId,
            'server_seed_hash' => $this->serverSeedHash,
            'started_at'       => now()->toIso8601String(),
        ];
    }
}
