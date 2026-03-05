<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast countdown ticks during the waiting phase.
 */
class GameCountdown implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $secondsLeft,
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('game');
    }

    public function broadcastAs(): string
    {
        return 'countdown.tick';
    }

    public function broadcastWith(): array
    {
        return [
            'seconds_left' => $this->secondsLeft,
        ];
    }
}
