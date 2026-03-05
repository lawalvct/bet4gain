<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast when a new betting phase begins.
 * Clients should open bet placement UI.
 */
class GameBettingStarted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int    $roundId,
        public readonly string $serverSeedHash,
        public readonly int    $bettingDuration, // seconds
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('game');
    }

    public function broadcastAs(): string
    {
        return 'betting.started';
    }

    public function broadcastWith(): array
    {
        return [
            'round_id'          => $this->roundId,
            'server_seed_hash'  => $this->serverSeedHash,
            'betting_duration'  => $this->bettingDuration,
            'timestamp'         => now()->toIso8601String(),
        ];
    }
}
