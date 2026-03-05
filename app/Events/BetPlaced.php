<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast when a player places a bet.
 * Used to update the live bets feed for all spectators.
 */
class BetPlaced implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int    $betId,
        public readonly int    $roundId,
        public readonly string $username,
        public readonly ?string $avatar,
        public readonly float  $amount,
        public readonly int    $betSlot,
        public readonly string $currency,
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('game');
    }

    public function broadcastAs(): string
    {
        return 'bet.placed';
    }

    public function broadcastWith(): array
    {
        return [
            'id'       => $this->betId,
            'round_id' => $this->roundId,
            'username' => $this->username,
            'avatar'   => $this->avatar,
            'amount'   => $this->amount,
            'bet_slot' => $this->betSlot,
            'currency' => $this->currency,
        ];
    }
}
