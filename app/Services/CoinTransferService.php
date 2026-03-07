<?php

namespace App\Services;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\CoinBalance;
use App\Models\CoinTransfer;
use App\Models\Transaction;
use App\Models\User;
use App\Events\CoinTransferReceived;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CoinTransferService
{
    /**
     * Transfer coins from one user to another.
     *
     * @param  User   $sender
     * @param  User   $recipient
     * @param  float  $amount     Coins to send
     * @param  string $type       'gift' or 'transfer'
     * @param  string $note       Optional note
     * @return CoinTransfer
     */
    public function transfer(User $sender, User $recipient, float $amount, string $type = 'transfer', string $note = ''): CoinTransfer
    {
        $this->validateTransfer($sender, $recipient, $amount);

        $feePercent = (float) config('payment.transfer_fee_percent', 2);
        $fee        = round($amount * ($feePercent / 100), 4);
        $netAmount  = round($amount - $fee, 4);

        $coinTransfer = DB::transaction(function () use ($sender, $recipient, $amount, $fee, $netAmount, $type, $note) {
            // Lock sender coin balance
            $senderCoins = CoinBalance::where('user_id', $sender->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (!$senderCoins->hasEnough($amount)) {
                throw new \RuntimeException('Insufficient coin balance.');
            }

            // Lock recipient coin balance
            $recipientCoins = CoinBalance::where('user_id', $recipient->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Debit sender (full amount including fee)
            $senderCoins->decrement('balance', $amount);

            // Credit recipient (net amount after fee)
            $recipientCoins->increment('balance', $netAmount);

            // Create transfer record
            $reference = CoinTransfer::generateReference();
            $coinTransfer = CoinTransfer::create([
                'sender_id'    => $sender->id,
                'recipient_id' => $recipient->id,
                'amount'       => $amount,
                'fee'          => $fee,
                'net_amount'   => $netAmount,
                'reference'    => $reference,
                'type'         => $type,
                'note'         => $note ?: null,
                'status'       => 'completed',
            ]);

            // Create transaction record for sender
            Transaction::create([
                'user_id'     => $sender->id,
                'type'        => TransactionType::CoinTransferSent,
                'amount'      => $amount,
                'currency'    => 'COINS',
                'reference'   => $reference . '_S',
                'status'      => TransactionStatus::Completed,
                'description' => "Sent " . number_format($amount, 2) . " coins to {$recipient->username}" . ($fee > 0 ? " (fee: " . number_format($fee, 2) . ")" : ""),
                'metadata'    => [
                    'transfer_id'    => $coinTransfer->id,
                    'recipient_id'   => $recipient->id,
                    'recipient_name' => $recipient->username,
                    'fee'            => $fee,
                    'net_amount'     => $netAmount,
                    'type'           => $type,
                ],
            ]);

            // Create transaction record for recipient
            Transaction::create([
                'user_id'     => $recipient->id,
                'type'        => TransactionType::CoinTransferReceived,
                'amount'      => $netAmount,
                'currency'    => 'COINS',
                'reference'   => $reference . '_R',
                'status'      => TransactionStatus::Completed,
                'description' => "Received " . number_format($netAmount, 2) . " coins from {$sender->username}",
                'metadata'    => [
                    'transfer_id' => $coinTransfer->id,
                    'sender_id'   => $sender->id,
                    'sender_name' => $sender->username,
                    'type'        => $type,
                ],
            ]);

            Log::info('Coin transfer completed', [
                'reference'    => $reference,
                'sender_id'    => $sender->id,
                'recipient_id' => $recipient->id,
                'amount'       => $amount,
                'fee'          => $fee,
                'net_amount'   => $netAmount,
            ]);

            return $coinTransfer;
        });

        // Broadcast notification to recipient (outside DB transaction)
        event(new CoinTransferReceived(
            recipientId:    $recipient->id,
            senderUsername: $sender->username,
            amount:         $netAmount,
            type:           $type,
            note:           $note ?: null,
        ));

        return $coinTransfer;
    }

    /**
     * Validate a transfer before executing.
     */
    private function validateTransfer(User $sender, User $recipient, float $amount): void
    {
        // Cannot send to self
        if ($sender->id === $recipient->id) {
            throw new \InvalidArgumentException('You cannot transfer coins to yourself.');
        }

        // Amount range
        $min = (float) config('payment.min_transfer', 100);
        $max = (float) config('payment.max_transfer', 100000);

        if ($amount < $min) {
            throw new \InvalidArgumentException("Minimum transfer is " . number_format($min) . " coins.");
        }

        if ($amount > $max) {
            throw new \InvalidArgumentException("Maximum transfer is " . number_format($max) . " coins.");
        }

        // Account age check
        $minAgeDays = (int) config('payment.min_account_age_days', 3);
        if ($sender->created_at->diffInDays(now()) < $minAgeDays) {
            throw new \RuntimeException("Your account must be at least {$minAgeDays} days old to transfer coins.");
        }

        // Daily transfer limit
        $dailyLimit = (float) config('payment.daily_transfer_limit', 500000);
        $todaysSent = CoinTransfer::where('sender_id', $sender->id)
            ->where('status', 'completed')
            ->whereDate('created_at', today())
            ->sum('amount');

        if (($todaysSent + $amount) > $dailyLimit) {
            $remaining = $dailyLimit - $todaysSent;
            throw new \RuntimeException(
                "Daily transfer limit exceeded. You can still send " . number_format(max(0, $remaining)) . " coins today."
            );
        }

        // Guest users cannot transfer
        if ($sender->is_guest ?? false) {
            throw new \RuntimeException('Guest accounts cannot transfer coins. Please register first.');
        }
    }

    /**
     * Get transfer history for a user.
     */
    public function getHistory(User $user, int $perPage = 20): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return CoinTransfer::where('sender_id', $user->id)
            ->orWhere('recipient_id', $user->id)
            ->with(['sender:id,username,avatar', 'recipient:id,username,avatar'])
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * Get daily sent total for a user.
     */
    public function getDailySentTotal(User $user): float
    {
        return (float) CoinTransfer::where('sender_id', $user->id)
            ->where('status', 'completed')
            ->whereDate('created_at', today())
            ->sum('amount');
    }
}
