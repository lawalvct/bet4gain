<?php

namespace App\Http\Controllers;

use App\Services\NombaService;
use App\Services\PaystackService;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(
        private readonly WalletService   $walletService,
        private readonly PaystackService $paystackService,
        private readonly NombaService    $nombaService,
    ) {}

    // ────── Paystack Webhook ──────

    /**
     * POST /api/payments/paystack/webhook
     * Handles Paystack webhook events.
     */
    public function paystackWebhook(Request $request): JsonResponse
    {
        $payload   = $request->getContent();
        $signature = $request->header('x-paystack-signature', '');

        if (!$this->paystackService->verifyWebhookSignature($payload, $signature)) {
            Log::warning('Paystack webhook: invalid signature');
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        $event = $request->input('event');
        $data  = $request->input('data', []);

        Log::info("Paystack webhook received: {$event}", ['reference' => $data['reference'] ?? null]);

        match ($event) {
            'charge.success'   => $this->handlePaystackChargeSuccess($data),
            'transfer.success' => $this->handlePaystackTransferSuccess($data),
            'transfer.failed'  => $this->handlePaystackTransferFailed($data),
            'transfer.reversed'=> $this->handlePaystackTransferFailed($data),
            default            => Log::info("Paystack webhook: unhandled event '{$event}'"),
        };

        return response()->json(['message' => 'Webhook processed']);
    }

    private function handlePaystackChargeSuccess(array $data): void
    {
        $reference = $data['reference'] ?? null;
        if (!$reference) return;

        try {
            // Always verify on our end before crediting
            $verification = $this->paystackService->verifyTransaction($reference);

            if (($verification['status'] ?? '') === 'success') {
                $this->walletService->completeDeposit($reference, 'paystack');
            } else {
                $this->walletService->failDeposit($reference, 'Paystack verification returned: ' . ($verification['status'] ?? 'unknown'));
            }
        } catch (\Throwable $e) {
            Log::error("Paystack charge.success handling failed: {$e->getMessage()}", [
                'reference' => $reference,
            ]);
        }
    }

    private function handlePaystackTransferSuccess(array $data): void
    {
        $reference = $data['reference'] ?? null;
        if (!$reference) return;

        try {
            $this->walletService->completeWithdrawal($reference);
        } catch (\Throwable $e) {
            Log::error("Paystack transfer.success handling failed: {$e->getMessage()}", [
                'reference' => $reference,
            ]);
        }
    }

    private function handlePaystackTransferFailed(array $data): void
    {
        $reference = $data['reference'] ?? null;
        if (!$reference) return;

        try {
            $transaction = \App\Models\Transaction::where('reference', $reference)->first();
            if ($transaction) {
                $this->walletService->reverseWithdrawal(
                    $transaction,
                    'Transfer failed: ' . ($data['reason'] ?? $data['message'] ?? 'unknown')
                );
            }
        } catch (\Throwable $e) {
            Log::error("Paystack transfer.failed handling failed: {$e->getMessage()}", [
                'reference' => $reference,
            ]);
        }
    }

    // ────── Nomba Webhook ──────

    /**
     * POST /api/payments/nomba/webhook
     * Handles Nomba webhook events.
     */
    public function nombaWebhook(Request $request): JsonResponse
    {
        $payload   = $request->getContent();
        $signature = $request->header('x-nomba-signature', '');

        if (!$this->nombaService->verifyWebhookSignature($payload, $signature)) {
            Log::warning('Nomba webhook: invalid signature');
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        $event = $request->input('event') ?? $request->input('type', '');
        $data  = $request->input('data', []);

        Log::info("Nomba webhook received: {$event}", $data);

        match ($event) {
            'checkout.completed', 'payment.success' => $this->handleNombaPaymentSuccess($data),
            'transfer.success'                      => $this->handleNombaTransferSuccess($data),
            'transfer.failed'                       => $this->handleNombaTransferFailed($data),
            default                                 => Log::info("Nomba webhook: unhandled event '{$event}'"),
        };

        return response()->json(['message' => 'Webhook processed']);
    }

    private function handleNombaPaymentSuccess(array $data): void
    {
        $reference = $data['reference'] ?? $data['order_reference'] ?? null;
        if (!$reference) return;

        try {
            $verification = $this->nombaService->verifyTransaction($reference);

            $status = $verification['status'] ?? $verification['data']['status'] ?? '';
            if (in_array(strtolower($status), ['success', 'completed', 'successful'])) {
                $this->walletService->completeDeposit($reference, 'nomba');
            } else {
                $this->walletService->failDeposit($reference, "Nomba verification status: {$status}");
            }
        } catch (\Throwable $e) {
            Log::error("Nomba payment.success handling failed: {$e->getMessage()}", [
                'reference' => $reference,
            ]);
        }
    }

    private function handleNombaTransferSuccess(array $data): void
    {
        $reference = $data['reference'] ?? null;
        if (!$reference) return;

        try {
            $this->walletService->completeWithdrawal($reference);
        } catch (\Throwable $e) {
            Log::error("Nomba transfer.success handling failed: {$e->getMessage()}", [
                'reference' => $reference,
            ]);
        }
    }

    private function handleNombaTransferFailed(array $data): void
    {
        $reference = $data['reference'] ?? null;
        if (!$reference) return;

        try {
            $transaction = \App\Models\Transaction::where('reference', $reference)->first();
            if ($transaction) {
                $this->walletService->reverseWithdrawal(
                    $transaction,
                    'Nomba transfer failed: ' . ($data['reason'] ?? 'unknown')
                );
            }
        } catch (\Throwable $e) {
            Log::error("Nomba transfer.failed handling failed: {$e->getMessage()}", [
                'reference' => $reference,
            ]);
        }
    }

    // ────── Gateway Callbacks (redirect after payment) ──────

    /**
     * GET /api/payments/{gateway}/callback
     * Called when user is redirected back from payment gateway.
     */
    public function callback(Request $request, string $gateway): \Illuminate\Http\RedirectResponse
    {
        $reference = $request->query('reference') ?? $request->query('trxref') ?? $request->query('order_reference');

        if (!$reference) {
            Log::warning("Payment callback missing reference for gateway: {$gateway}");
            return redirect('/wallet?payment=error&message=missing_reference');
        }

        try {
            match ($gateway) {
                'paystack' => $this->verifyPaystackCallback($reference),
                'nomba'    => $this->verifyNombaCallback($reference),
                default    => throw new \InvalidArgumentException("Unsupported gateway: {$gateway}"),
            };

            return redirect("/wallet?payment=success&reference={$reference}");
        } catch (\Throwable $e) {
            Log::error("Payment callback error ({$gateway}): {$e->getMessage()}", [
                'reference' => $reference,
            ]);
            return redirect("/wallet?payment=error&reference={$reference}");
        }
    }

    private function verifyPaystackCallback(string $reference): void
    {
        $verification = $this->paystackService->verifyTransaction($reference);

        if (($verification['status'] ?? '') === 'success') {
            $this->walletService->completeDeposit($reference, 'paystack');
        }
    }

    private function verifyNombaCallback(string $reference): void
    {
        $verification = $this->nombaService->verifyTransaction($reference);

        $status = $verification['status'] ?? $verification['data']['status'] ?? '';
        if (in_array(strtolower($status), ['success', 'completed', 'successful'])) {
            $this->walletService->completeDeposit($reference, 'nomba');
        }
    }
}
