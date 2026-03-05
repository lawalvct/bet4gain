<?php

namespace App\Services;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\CoinBalance;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Central wallet/balance operations service.
 *
 * All balance mutations go through this service to ensure:
 *   - Database transactions with row-level locks
 *   - Idempotency via unique transaction references
 *   - Audit trail (Transaction records for every operation)
 *   - Proper coin ↔ NGN rate conversion
 */
class WalletService
{
    // ── Deposit Flow ────────────────────────────────────────────────────────

    /**
     * Initialize a deposit via the selected payment gateway.
     *
     * Creates a pending Transaction, then calls the gateway to get a checkout URL.
     *
     * @param  User   $user
     * @param  float  $amount    Amount in NGN
     * @param  string $gateway   'paystack' or 'nomba'
     * @return array{transaction: Transaction, checkout_url: string, reference: string}
     */
    public function initializeDeposit(User $user, float $amount, string $gateway = 'paystack'): array
    {
        $this->validateDepositAmount($amount);

        $reference = Transaction::generateReference('DEP');

        // Create pending transaction record
        $transaction = Transaction::create([
            'user_id'     => $user->id,
            'type'        => TransactionType::Deposit,
            'amount'      => $amount,
            'currency'    => config('payment.currency', 'NGN'),
            'reference'   => $reference,
            'gateway'     => $gateway,
            'status'      => TransactionStatus::Pending,
            'description' => "Deposit of ₦" . number_format($amount, 2) . " via {$gateway}",
            'metadata'    => ['initiated_at' => now()->toIso8601String()],
        ]);

        $callbackUrl = config("payment.{$gateway}.callback_url")
            ?: url("/api/payments/{$gateway}/callback");

        if ($gateway === 'paystack') {
            $service = new PaystackService();
            $result  = $service->initializeTransaction(
                email:       $user->email,
                amountKobo:  (int) ($amount * 100),
                reference:   $reference,
                callbackUrl: $callbackUrl,
                metadata:    ['user_id' => $user->id, 'type' => 'deposit'],
            );

            $transaction->update([
                'gateway_reference' => $result['access_code'] ?? null,
            ]);

            return [
                'transaction'       => $transaction,
                'authorization_url' => $result['authorization_url'],
                'reference'         => $reference,
            ];
        }

        if ($gateway === 'nomba') {
            $service = new NombaService();
            $result  = $service->createCheckout(
                amount:      $amount,
                reference:   $reference,
                callbackUrl: $callbackUrl,
                email:       $user->email,
                metadata:    ['user_id' => $user->id, 'type' => 'deposit'],
            );

            $transaction->update([
                'gateway_reference' => $result['order_reference'] ?? null,
            ]);

            return [
                'transaction'       => $transaction,
                'authorization_url' => $result['checkout_url'] ?? $result['checkoutUrl'] ?? '',
                'reference'         => $reference,
            ];
        }

        throw new \InvalidArgumentException("Unsupported gateway: {$gateway}");
    }

    /**
     * Complete a deposit after successful payment verification.
     * Idempotent — safe to call multiple times for the same reference.
     *
     * @param  string  $reference Transaction reference
     * @param  string  $gateway   Gateway name
     * @return Transaction
     */
    public function completeDeposit(string $reference, string $gateway): Transaction
    {
        return DB::transaction(function () use ($reference, $gateway) {
            $transaction = Transaction::where('reference', $reference)
                ->lockForUpdate()
                ->firstOrFail();

            // Idempotency: already completed
            if ($transaction->status === TransactionStatus::Completed) {
                return $transaction;
            }

            if ($transaction->status !== TransactionStatus::Pending) {
                throw new \RuntimeException("Transaction {$reference} is not pending (status: {$transaction->status->value}).");
            }

            // Credit the wallet
            $wallet = Wallet::where('user_id', $transaction->user_id)
                ->lockForUpdate()
                ->firstOrFail();

            $wallet->increment('balance', (float) $transaction->amount);

            $transaction->update([
                'status'   => TransactionStatus::Completed,
                'metadata' => array_merge($transaction->metadata ?? [], [
                    'completed_at' => now()->toIso8601String(),
                    'gateway'      => $gateway,
                ]),
            ]);

            Log::info("Deposit completed", [
                'user_id'   => $transaction->user_id,
                'amount'    => $transaction->amount,
                'reference' => $reference,
            ]);

            return $transaction;
        });
    }

    /**
     * Fail a deposit transaction.
     */
    public function failDeposit(string $reference, string $reason = ''): Transaction
    {
        $transaction = Transaction::where('reference', $reference)->firstOrFail();

        if ($transaction->status !== TransactionStatus::Pending) {
            return $transaction;
        }

        $transaction->update([
            'status'   => TransactionStatus::Failed,
            'metadata' => array_merge($transaction->metadata ?? [], [
                'failed_at' => now()->toIso8601String(),
                'reason'    => $reason,
            ]),
        ]);

        return $transaction;
    }

    // ── Withdrawal Flow ─────────────────────────────────────────────────────

    /**
     * Request a withdrawal.
     *
     * Deducts from wallet immediately (optimistic hold), creates pending transaction.
     * Admin approval or auto-approve below threshold.
     *
     * @param  User   $user
     * @param  float  $amount       Amount in NGN
     * @param  string $gateway
     * @param  array  $bankDetails  [account_number, bank_code, account_name]
     * @return Transaction
     */
    public function requestWithdrawal(
        User   $user,
        float  $amount,
        string $gateway = 'paystack',
        array  $bankDetails = [],
    ): Transaction {
        $this->validateWithdrawalAmount($amount);
        $fee   = $this->calculateWithdrawalFee($amount);
        $total = $amount + $fee;

        return DB::transaction(function () use ($user, $amount, $fee, $total, $gateway, $bankDetails) {
            $wallet = Wallet::where('user_id', $user->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($wallet->is_locked) {
                throw new \RuntimeException('Wallet is locked. Contact support.');
            }

            if (!$wallet->hasEnough($total)) {
                throw new \RuntimeException('Insufficient wallet balance.');
            }

            // Deduct immediately (hold)
            $wallet->decrement('balance', $total);

            $reference = Transaction::generateReference('WDR');

            $transaction = Transaction::create([
                'user_id'     => $user->id,
                'type'        => TransactionType::Withdrawal,
                'amount'      => $amount,
                'currency'    => config('payment.currency', 'NGN'),
                'reference'   => $reference,
                'gateway'     => $gateway,
                'status'      => TransactionStatus::Pending,
                'description' => "Withdrawal of ₦" . number_format($amount, 2) . " (fee: ₦" . number_format($fee, 2) . ")",
                'metadata'    => [
                    'fee'            => $fee,
                    'total_deducted' => $total,
                    'bank_details'   => $bankDetails,
                    'requested_at'   => now()->toIso8601String(),
                ],
            ]);

            // Auto-approve if below threshold
            $autoApproveLimit = config('payment.auto_approve_withdrawal_limit', 50000);
            if ($amount <= $autoApproveLimit) {
                $this->processWithdrawal($transaction);
            }

            return $transaction;
        });
    }

    /**
     * Process an approved withdrawal — send to gateway.
     *
     * @param  Transaction  $transaction
     */
    public function processWithdrawal(Transaction $transaction): void
    {
        $bankDetails = $transaction->metadata['bank_details'] ?? [];

        try {
            if ($transaction->gateway === 'paystack') {
                $service   = new PaystackService();
                $recipient = $service->createTransferRecipient(
                    name:          $bankDetails['account_name'] ?? '',
                    accountNumber: $bankDetails['account_number'] ?? '',
                    bankCode:      $bankDetails['bank_code'] ?? '',
                );
                $transfer = $service->initiateTransfer(
                    amountKobo:    (int) ($transaction->amount * 100),
                    recipientCode: $recipient['recipient_code'],
                    reference:     $transaction->reference,
                    reason:        'Bet4Gain Withdrawal',
                );

                $transaction->update([
                    'gateway_reference' => $transfer['transfer_code'] ?? null,
                    'metadata' => array_merge($transaction->metadata ?? [], [
                        'transfer_data' => $transfer,
                        'processed_at'  => now()->toIso8601String(),
                    ]),
                ]);
            } elseif ($transaction->gateway === 'nomba') {
                $service  = new NombaService();
                $transfer = $service->initiateTransfer(
                    amount:        (float) $transaction->amount,
                    accountNumber: $bankDetails['account_number'] ?? '',
                    bankCode:      $bankDetails['bank_code'] ?? '',
                    accountName:   $bankDetails['account_name'] ?? '',
                    reference:     $transaction->reference,
                );

                $transaction->update([
                    'gateway_reference' => $transfer['transfer_reference'] ?? null,
                    'metadata' => array_merge($transaction->metadata ?? [], [
                        'transfer_data' => $transfer,
                        'processed_at'  => now()->toIso8601String(),
                    ]),
                ]);
            }
        } catch (\Throwable $e) {
            Log::error("Withdrawal processing failed: " . $e->getMessage(), [
                'transaction_id' => $transaction->id,
                'reference'      => $transaction->reference,
            ]);
            // Return funds to wallet on failure
            $this->reverseWithdrawal($transaction, $e->getMessage());
            throw $e;
        }
    }

    /**
     * Complete a withdrawal (called by webhook on successful transfer).
     */
    public function completeWithdrawal(string $reference): Transaction
    {
        return DB::transaction(function () use ($reference) {
            $transaction = Transaction::where('reference', $reference)
                ->lockForUpdate()
                ->firstOrFail();

            if ($transaction->status === TransactionStatus::Completed) {
                return $transaction;
            }

            $transaction->update([
                'status'   => TransactionStatus::Completed,
                'metadata' => array_merge($transaction->metadata ?? [], [
                    'completed_at' => now()->toIso8601String(),
                ]),
            ]);

            return $transaction;
        });
    }

    /**
     * Reverse a failed withdrawal — return funds to wallet.
     */
    public function reverseWithdrawal(Transaction $transaction, string $reason = ''): void
    {
        DB::transaction(function () use ($transaction, $reason) {
            $totalDeducted = $transaction->metadata['total_deducted'] ?? (float) $transaction->amount;

            $wallet = Wallet::where('user_id', $transaction->user_id)
                ->lockForUpdate()
                ->firstOrFail();

            $wallet->increment('balance', $totalDeducted);

            $transaction->update([
                'status'   => TransactionStatus::Reversed,
                'metadata' => array_merge($transaction->metadata ?? [], [
                    'reversed_at' => now()->toIso8601String(),
                    'reason'      => $reason,
                ]),
            ]);
        });
    }

    // ── Coin Purchase / Sell ─────────────────────────────────────────────────

    /**
     * Purchase coins with wallet NGN balance.
     *
     * @param  User   $user
     * @param  float  $ngnAmount  Amount in NGN to spend
     * @return array{transaction: Transaction, coins_credited: float}
     */
    public function purchaseCoins(User $user, float $ngnAmount): array
    {
        $rate       = (float) config('payment.ngn_to_coin_rate', 1);
        $coinAmount = round($ngnAmount * $rate, 4);

        return DB::transaction(function () use ($user, $ngnAmount, $coinAmount) {
            $wallet = Wallet::where('user_id', $user->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (!$wallet->hasEnough($ngnAmount)) {
                throw new \RuntimeException('Insufficient wallet balance.');
            }

            $coinBalance = CoinBalance::where('user_id', $user->id)
                ->lockForUpdate()
                ->firstOrFail();

            $wallet->decrement('balance', $ngnAmount);
            $coinBalance->increment('balance', $coinAmount);

            $reference   = Transaction::generateReference('BUY');
            $transaction = Transaction::create([
                'user_id'     => $user->id,
                'type'        => TransactionType::PurchaseCoins,
                'amount'      => $ngnAmount,
                'currency'    => 'NGN',
                'reference'   => $reference,
                'status'      => TransactionStatus::Completed,
                'description' => "Purchased " . number_format($coinAmount, 2) . " coins for ₦" . number_format($ngnAmount, 2),
                'metadata'    => [
                    'coins_credited' => $coinAmount,
                    'rate'           => $coinAmount / $ngnAmount,
                ],
            ]);

            return [
                'transaction'    => $transaction,
                'coins_credited' => $coinAmount,
            ];
        });
    }

    /**
     * Sell coins back to wallet NGN balance.
     *
     * @param  User   $user
     * @param  float  $coinAmount  Number of coins to sell
     * @return array{transaction: Transaction, ngn_credited: float}
     */
    public function sellCoins(User $user, float $coinAmount): array
    {
        $rate      = (float) config('payment.coin_to_ngn_rate', 1);
        $ngnAmount = round($coinAmount * $rate, 4);

        return DB::transaction(function () use ($user, $coinAmount, $ngnAmount) {
            $coinBalance = CoinBalance::where('user_id', $user->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (!$coinBalance->hasEnough($coinAmount)) {
                throw new \RuntimeException('Insufficient coin balance.');
            }

            $wallet = Wallet::where('user_id', $user->id)
                ->lockForUpdate()
                ->firstOrFail();

            $coinBalance->decrement('balance', $coinAmount);
            $wallet->increment('balance', $ngnAmount);

            $reference   = Transaction::generateReference('SELL');
            $transaction = Transaction::create([
                'user_id'     => $user->id,
                'type'        => TransactionType::SellCoins,
                'amount'      => $ngnAmount,
                'currency'    => 'NGN',
                'reference'   => $reference,
                'status'      => TransactionStatus::Completed,
                'description' => "Sold " . number_format($coinAmount, 2) . " coins for ₦" . number_format($ngnAmount, 2),
                'metadata'    => [
                    'coins_sold' => $coinAmount,
                    'rate'       => $ngnAmount / $coinAmount,
                ],
            ]);

            return [
                'transaction'  => $transaction,
                'ngn_credited' => $ngnAmount,
            ];
        });
    }

    // ── Validation Helpers ──────────────────────────────────────────────────

    private function validateDepositAmount(float $amount): void
    {
        $min = config('payment.min_deposit', 500);
        $max = config('payment.max_deposit', 1000000);

        if ($amount < $min) {
            throw new \InvalidArgumentException("Minimum deposit is ₦" . number_format($min, 2));
        }
        if ($amount > $max) {
            throw new \InvalidArgumentException("Maximum deposit is ₦" . number_format($max, 2));
        }
    }

    private function validateWithdrawalAmount(float $amount): void
    {
        $min = config('payment.min_withdrawal', 1000);
        $max = config('payment.max_withdrawal', 500000);

        if ($amount < $min) {
            throw new \InvalidArgumentException("Minimum withdrawal is ₦" . number_format($min, 2));
        }
        if ($amount > $max) {
            throw new \InvalidArgumentException("Maximum withdrawal is ₦" . number_format($max, 2));
        }
    }

    private function calculateWithdrawalFee(float $amount): float
    {
        $feePercent = (float) config('payment.withdrawal_fee_percent', 1);
        return round($amount * ($feePercent / 100), 2);
    }
}
