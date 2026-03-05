<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast current multiplier during a running round.
 * Sent every ~100 ms. Clients interpolate for 60fps smoothness.
 */
class GameMultiplierUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int   $roundId,
        public readonly float $multiplier,
        public readonly int   $elapsedMs,
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('game');
    }

    public function broadcastAs(): string
    {
        return 'multiplier.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'round_id'   => $this->roundId,
            'multiplier' => round($this->multiplier, 2),
            'elapsed_ms' => $this->elapsedMs,
            'ts'         => now()->getPreciseTimestamp(3), // ms timestamp
        ];
    }
}
