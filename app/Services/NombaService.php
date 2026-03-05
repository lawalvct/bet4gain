<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Nomba payment gateway integration.
 *
 * Handles:
 *   - Checkout creation (deposit)
 *   - Transaction verification
 *   - Transfer creation (withdrawal)
 *   - Webhook signature verification
 *
 * @see https://developer.nomba.com/
 */
class NombaService
{
    private string $clientId;
    private string $clientSecret;
    private string $baseUrl;
    private string $accountId;

    public function __construct()
    {
        $this->clientId     = config('payment.nomba.client_id', '');
        $this->clientSecret = config('payment.nomba.client_secret', '');
        $this->baseUrl      = config('payment.nomba.base_url', 'https://api.nomba.com/v1');
        $this->accountId    = config('payment.nomba.account_id', '');
    }

    /**
     * Get an access token for API calls.
     *
     * @return string Bearer token
     *
     * @throws \RuntimeException
     */
    private function getAccessToken(): string
    {
        $cacheKey = 'nomba_access_token';
        $cached   = cache($cacheKey);

        if ($cached) {
            return $cached;
        }

        $response = Http::post("{$this->baseUrl}/auth/token/issue", [
            'grant_type'    => 'client_credentials',
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
        ]);

        if (!$response->successful()) {
            Log::error('Nomba auth failed', ['response' => $response->json()]);
            throw new \RuntimeException('Failed to authenticate with Nomba.');
        }

        $data  = $response->json('data');
        $token = $data['access_token'] ?? '';
        $ttl   = ($data['expires_in'] ?? 3600) - 60; // Buffer 60s

        cache([$cacheKey => $token], now()->addSeconds($ttl));

        return $token;
    }

    /**
     * Create a checkout session for deposits.
     *
     * @param  float   $amount     Amount in NGN
     * @param  string  $reference  Unique reference
     * @param  string  $callbackUrl Success redirect URL
     * @param  string  $email      Customer email
     * @param  array   $metadata   Extra metadata
     * @return array{checkout_url: string, order_reference: string, ...}
     *
     * @throws \RuntimeException
     */
    public function createCheckout(
        float  $amount,
        string $reference,
        string $callbackUrl,
        string $email,
        array  $metadata = [],
    ): array {
        $token = $this->getAccessToken();

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
            'Content-Type'  => 'application/json',
        ])->post("{$this->baseUrl}/checkout/order", [
            'order' => [
                'orderReference' => $reference,
                'customerId'     => $email,
                'callbackUrl'    => $callbackUrl,
                'amount'         => [
                    'value'    => $amount,
                    'currency' => 'NGN',
                ],
                'customFields' => $metadata,
            ],
        ]);

        if (!$response->successful()) {
            Log::error('Nomba checkout failed', ['response' => $response->json()]);
            throw new \RuntimeException(
                $response->json('message', 'Failed to create Nomba checkout.')
            );
        }

        return $response->json('data');
    }

    /**
     * Verify a transaction/order by reference.
     *
     * @param  string  $orderReference
     * @return array{status: string, amount: array, ...}
     *
     * @throws \RuntimeException
     */
    public function verifyTransaction(string $orderReference): array
    {
        $token = $this->getAccessToken();

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->get("{$this->baseUrl}/checkout/order/{$orderReference}");

        if (!$response->successful()) {
            Log::error('Nomba verify failed', [
                'reference' => $orderReference,
                'response'  => $response->json(),
            ]);
            throw new \RuntimeException(
                $response->json('message', 'Failed to verify Nomba transaction.')
            );
        }

        return $response->json('data');
    }

    /**
     * Initiate a transfer (withdrawal) to a bank account.
     *
     * @param  float   $amount       Amount in NGN
     * @param  string  $accountNumber Bank account number
     * @param  string  $bankCode     Bank code
     * @param  string  $accountName  Account holder name
     * @param  string  $reference    Unique reference
     * @return array
     *
     * @throws \RuntimeException
     */
    public function initiateTransfer(
        float  $amount,
        string $accountNumber,
        string $bankCode,
        string $accountName,
        string $reference,
    ): array {
        $token = $this->getAccessToken();

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
            'Content-Type'  => 'application/json',
        ])->post("{$this->baseUrl}/transfers/bank", [
            'accountNumber' => $this->accountId,
            'amount'        => [
                'value'    => $amount,
                'currency' => 'NGN',
            ],
            'beneficiary' => [
                'accountNumber'  => $accountNumber,
                'accountName'    => $accountName,
                'bankCode'       => $bankCode,
            ],
            'merchantTxRef' => $reference,
        ]);

        if (!$response->successful()) {
            Log::error('Nomba transfer failed', ['response' => $response->json()]);
            throw new \RuntimeException(
                $response->json('message', 'Failed to initiate Nomba transfer.')
            );
        }

        return $response->json('data');
    }

    /**
     * Verify webhook signature.
     *
     * @param  string  $payload   Raw request body
     * @param  string  $signature Webhook signature header
     * @return bool
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        $webhookSecret = config('payment.nomba.webhook_secret', '');
        $computed      = hash_hmac('sha512', $payload, $webhookSecret);
        return hash_equals($computed, $signature);
    }

    /**
     * Check if Nomba is enabled.
     */
    public function isEnabled(): bool
    {
        return config('payment.nomba.enabled', false) && !empty($this->clientId);
    }
}
