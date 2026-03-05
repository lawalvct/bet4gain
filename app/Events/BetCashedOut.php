<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast when a player cashes out.
 * Updates live bets feed to show cashout multiplier & profit.
 */
class BetCashedOut implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int   $betId,
        public readonly int   $roundId,
        public readonly string $username,
        public readonly float $amount,
        public readonly float $cashedOutAt,
        public readonly float $payout,
        public readonly bool  $isAuto,
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('game');
    }

    public function broadcastAs(): string
    {
        return 'bet.cashed_out';
    }

    public function broadcastWith(): array
    {
        return [
            'id'            => $this->betId,
            'round_id'      => $this->roundId,
            'username'      => $this->username,
            'amount'        => $this->amount,
            'cashed_out_at' => $this->cashedOutAt,
            'payout'        => $this->payout,
            'profit'        => round($this->payout - $this->amount, 4),
            'is_auto'       => $this->isAuto,
        ];
    }
}
