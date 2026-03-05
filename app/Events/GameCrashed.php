<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast when the game crashes.
 * Includes the final crash point and server seed for verification.
 */
class GameCrashed implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int    $roundId,
        public readonly float  $crashPoint,
        public readonly string $serverSeed,   // revealed after crash
        public readonly string $clientSeed,
        public readonly int    $nonce,
        public readonly int    $durationMs,
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('game');
    }

    public function broadcastAs(): string
    {
        return 'round.crashed';
    }

    public function broadcastWith(): array
    {
        return [
            'round_id'    => $this->roundId,
            'crash_point' => $this->crashPoint,
            'server_seed' => $this->serverSeed,
            'client_seed' => $this->clientSeed,
            'nonce'       => $this->nonce,
            'duration_ms' => $this->durationMs,
            'crashed_at'  => now()->toIso8601String(),
        ];
    }
}
