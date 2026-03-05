<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Paystack payment gateway integration.
 *
 * Handles:
 *   - Transaction initialization (deposit)
 *   - Transaction verification
 *   - Transfer creation (withdrawal)
 *   - Transfer verification
 *   - Webhook signature verification
 *
 * @see https://paystack.com/docs/api
 */
class PaystackService
{
    private string $secretKey;
    private string $publicKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->secretKey = config('payment.paystack.secret_key', '');
        $this->publicKey = config('payment.paystack.public_key', '');
        $this->baseUrl   = config('payment.paystack.base_url', 'https://api.paystack.co');
    }

    /**
     * Initialize a deposit transaction.
     * Returns authorization_url to redirect user to Paystack checkout.
     *
     * @param  string  $email      User email
     * @param  int     $amountKobo Amount in kobo (NGN * 100)
     * @param  string  $reference  Unique transaction reference
     * @param  string  $callbackUrl URL to redirect after payment
     * @param  array   $metadata   Extra data (user_id, etc.)
     * @return array{status: bool, authorization_url: string, access_code: string, reference: string}
     *
     * @throws \RuntimeException
     */
    public function initializeTransaction(
        string $email,
        int    $amountKobo,
        string $reference,
        string $callbackUrl,
        array  $metadata = [],
    ): array {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->secretKey}",
            'Content-Type'  => 'application/json',
        ])->post("{$this->baseUrl}/transaction/initialize", [
            'email'        => $email,
            'amount'       => $amountKobo,
            'reference'    => $reference,
            'callback_url' => $callbackUrl,
            'metadata'     => $metadata,
            'currency'     => 'NGN',
        ]);

        if (!$response->successful() || !$response->json('status')) {
            Log::error('Paystack init failed', [
                'status'   => $response->status(),
                'response' => $response->json(),
            ]);
            throw new \RuntimeException(
                $response->json('message', 'Failed to initialize Paystack transaction.')
            );
        }

        return $response->json('data');
    }

    /**
     * Verify a transaction by reference.
     *
     * @param  string  $reference
     * @return array{status: string, amount: int, currency: string, reference: string, gateway_response: string, ...}
     *
     * @throws \RuntimeException
     */
    public function verifyTransaction(string $reference): array
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->secretKey}",
        ])->get("{$this->baseUrl}/transaction/verify/{$reference}");

        if (!$response->successful() || !$response->json('status')) {
            Log::error('Paystack verify failed', [
                'reference' => $reference,
                'response'  => $response->json(),
            ]);
            throw new \RuntimeException(
                $response->json('message', 'Failed to verify transaction.')
            );
        }

        return $response->json('data');
    }

    /**
     * Create a transfer recipient (bank account) for withdrawals.
     *
     * @param  string  $name         Account holder name
     * @param  string  $accountNumber Bank account number
     * @param  string  $bankCode     Bank code (from Paystack bank list)
     * @return array{recipient_code: string, ...}
     */
    public function createTransferRecipient(
        string $name,
        string $accountNumber,
        string $bankCode,
    ): array {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->secretKey}",
            'Content-Type'  => 'application/json',
        ])->post("{$this->baseUrl}/transferrecipient", [
            'type'           => 'nuban',
            'name'           => $name,
            'account_number' => $accountNumber,
            'bank_code'      => $bankCode,
            'currency'       => 'NGN',
        ]);

        if (!$response->successful() || !$response->json('status')) {
            throw new \RuntimeException(
                $response->json('message', 'Failed to create transfer recipient.')
            );
        }

        return $response->json('data');
    }

    /**
     * Initiate a transfer (withdrawal) to a bank account.
     *
     * @param  int     $amountKobo    Amount in kobo
     * @param  string  $recipientCode Transfer recipient code
     * @param  string  $reference     Unique reference
     * @param  string  $reason        Transfer reason/description
     * @return array{transfer_code: string, reference: string, status: string, ...}
     */
    public function initiateTransfer(
        int    $amountKobo,
        string $recipientCode,
        string $reference,
        string $reason = 'Withdrawal',
    ): array {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->secretKey}",
            'Content-Type'  => 'application/json',
        ])->post("{$this->baseUrl}/transfer", [
            'source'    => 'balance',
            'amount'    => $amountKobo,
            'recipient' => $recipientCode,
            'reference' => $reference,
            'reason'    => $reason,
        ]);

        if (!$response->successful() || !$response->json('status')) {
            throw new \RuntimeException(
                $response->json('message', 'Failed to initiate transfer.')
            );
        }

        return $response->json('data');
    }

    /**
     * List available banks (for withdrawal form).
     *
     * @return array
     */
    public function listBanks(): array
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->secretKey}",
        ])->get("{$this->baseUrl}/bank", ['country' => 'nigeria']);

        if (!$response->successful()) {
            return [];
        }

        return $response->json('data', []);
    }

    /**
     * Resolve a bank account number to get account name.
     *
     * @param  string  $accountNumber
     * @param  string  $bankCode
     * @return array{account_name: string, account_number: string}
     */
    public function resolveAccount(string $accountNumber, string $bankCode): array
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->secretKey}",
        ])->get("{$this->baseUrl}/bank/resolve", [
            'account_number' => $accountNumber,
            'bank_code'      => $bankCode,
        ]);

        if (!$response->successful() || !$response->json('status')) {
            throw new \RuntimeException(
                $response->json('message', 'Failed to resolve account.')
            );
        }

        return $response->json('data');
    }

    /**
     * Verify that a webhook request is genuinely from Paystack.
     *
     * @param  string  $payload     Raw request body
     * @param  string  $signature   X-Paystack-Signature header value
     * @return bool
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        $computed = hash_hmac('sha512', $payload, $this->secretKey);
        return hash_equals($computed, $signature);
    }

    /**
     * Get public key (for frontend Paystack popup).
     */
    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    /**
     * Check if Paystack is enabled.
     */
    public function isEnabled(): bool
    {
        return config('payment.paystack.enabled', false) && !empty($this->secretKey);
    }
}
