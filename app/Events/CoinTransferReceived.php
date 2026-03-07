<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast to the recipient when they receive coins via P2P transfer.
 * Fires on their private channel so only they see the notification.
 */
class CoinTransferReceived implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int    $recipientId,
        public readonly string $senderUsername,
        public readonly float  $amount,
        public readonly string $type,
        public readonly ?string $note,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("user.{$this->recipientId}");
    }

    public function broadcastAs(): string
    {
        return 'coin.transfer.received';
    }

    public function broadcastWith(): array
    {
        return [
            'sender'  => $this->senderUsername,
            'amount'  => $this->amount,
            'type'    => $this->type,
            'note'    => $this->note,
        ];
    }
}
