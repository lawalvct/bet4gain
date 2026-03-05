<?php

namespace App\Http\Controllers;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Services\PaystackService;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WalletController extends Controller
{
    public function __construct(
        private readonly WalletService $walletService,
    ) {}

    /**
     * GET /api/wallet
     * Returns wallet + coin balances.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load(['wallet', 'coinBalance']);

        return response()->json([
            'wallet' => $user->wallet,
            'coins'  => $user->coinBalance,
        ]);
    }

    /**
     * GET /api/wallet/transactions
     * Paginated transaction history with optional filters.
     */
    public function transactions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type'    => 'nullable|string',
            'status'  => 'nullable|string',
            'page'    => 'nullable|integer|min:1',
        ]);

        $query = $request->user()->transactions()->orderByDesc('created_at');

        if (!empty($validated['type'])) {
            $query->where('type', $validated['type']);
        }
        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $transactions = $query->paginate(20);

        return response()->json($transactions);
    }

    /**
     * GET /api/wallet/transactions/export
     * Export transactions as CSV.
     */
    public function exportTransactions(Request $request)
    {
        $transactions = $request->user()
            ->transactions()
            ->orderByDesc('created_at')
            ->limit(500)
            ->get();

        $filename = 'transactions_' . now()->format('Y-m-d_His') . '.csv';
        $headers  = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($transactions) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Date', 'Type', 'Amount', 'Currency', 'Status', 'Reference', 'Description']);

            foreach ($transactions as $tx) {
                fputcsv($file, [
                    $tx->created_at->format('Y-m-d H:i:s'),
                    $tx->type->value ?? $tx->type,
                    $tx->amount,
                    $tx->currency,
                    $tx->status->value ?? $tx->status,
                    $tx->reference,
                    $tx->description,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * POST /api/wallet/deposit
     * Initialize a deposit with selected gateway.
     */
    public function deposit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount'  => 'required|numeric|min:' . config('payment.min_deposit', 500) . '|max:' . config('payment.max_deposit', 1000000),
            'gateway' => 'nullable|string|in:paystack,nomba',
        ]);

        $user    = $request->user();
        $amount  = (float) $validated['amount'];
        $gateway = $validated['gateway'] ?? config('payment.default_gateway', 'paystack');

        try {
            $result = $this->walletService->initializeDeposit($user, $amount, $gateway);

            return response()->json([
                'message'           => 'Deposit initialized. Redirect to payment page.',
                'authorization_url' => $result['authorization_url'],
                'reference'         => $result['reference'],
            ]);
        } catch (\Throwable $e) {
            Log::error('Deposit initialization failed: ' . $e->getMessage());
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * POST /api/wallet/withdraw
     * Request a withdrawal to bank account.
     */
    public function withdraw(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount'         => 'required|numeric|min:' . config('payment.min_withdrawal', 1000) . '|max:' . config('payment.max_withdrawal', 500000),
            'gateway'        => 'nullable|string|in:paystack,nomba',
            'account_number' => 'required|string|size:10',
            'bank_code'      => 'required|string',
            'account_name'   => 'required|string|max:100',
        ]);

        $user    = $request->user();
        $amount  = (float) $validated['amount'];
        $gateway = $validated['gateway'] ?? config('payment.default_gateway', 'paystack');

        try {
            $transaction = $this->walletService->requestWithdrawal(
                user:        $user,
                amount:      $amount,
                gateway:     $gateway,
                bankDetails: [
                    'account_number' => $validated['account_number'],
                    'bank_code'      => $validated['bank_code'],
                    'account_name'   => $validated['account_name'],
                ],
            );

            $user->load('wallet');

            return response()->json([
                'message'     => 'Withdrawal requested successfully.',
                'transaction' => [
                    'id'        => $transaction->id,
                    'reference' => $transaction->reference,
                    'amount'    => $transaction->amount,
                    'status'    => $transaction->status->value,
                ],
                'wallet' => $user->wallet,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * POST /api/wallet/purchase-coins
     * Buy coins with NGN wallet balance.
     */
    public function purchaseCoins(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:10',
        ]);

        $user   = $request->user();
        $amount = (float) $validated['amount'];

        try {
            $result = $this->walletService->purchaseCoins($user, $amount);
            $user->load(['wallet', 'coinBalance']);

            return response()->json([
                'message'        => 'Coins purchased successfully!',
                'coins_credited' => $result['coins_credited'],
                'wallet'         => $user->wallet,
                'coins'          => $user->coinBalance,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * POST /api/wallet/sell-coins
     * Sell coins back to NGN wallet balance.
     */
    public function sellCoins(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:10',
        ]);

        $user       = $request->user();
        $coinAmount = (float) $validated['amount'];

        try {
            $result = $this->walletService->sellCoins($user, $coinAmount);
            $user->load(['wallet', 'coinBalance']);

            return response()->json([
                'message'      => 'Coins sold successfully!',
                'ngn_credited' => $result['ngn_credited'],
                'wallet'       => $user->wallet,
                'coins'        => $user->coinBalance,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * GET /api/wallet/gateways
     * Returns available payment gateways + config for frontend.
     */
    public function gateways(): JsonResponse
    {
        $gateways = [];

        $paystack = new PaystackService();
        if ($paystack->isEnabled()) {
            $gateways[] = [
                'id'         => 'paystack',
                'name'       => 'Paystack',
                'public_key' => $paystack->getPublicKey(),
                'enabled'    => true,
            ];
        }

        $nomba = new \App\Services\NombaService();
        if ($nomba->isEnabled()) {
            $gateways[] = [
                'id'      => 'nomba',
                'name'    => 'Nomba',
                'enabled' => true,
            ];
        }

        return response()->json([
            'gateways'       => $gateways,
            'default'        => config('payment.default_gateway', 'paystack'),
            'currency'       => config('payment.currency', 'NGN'),
            'coin_rate'      => config('payment.ngn_to_coin_rate', 1),
            'sell_rate'      => config('payment.coin_to_ngn_rate', 1),
            'min_deposit'    => config('payment.min_deposit', 500),
            'max_deposit'    => config('payment.max_deposit', 1000000),
            'min_withdrawal' => config('payment.min_withdrawal', 1000),
            'max_withdrawal' => config('payment.max_withdrawal', 500000),
            'withdrawal_fee' => config('payment.withdrawal_fee_percent', 1),
        ]);
    }

    /**
     * GET /api/wallet/banks
     * Returns list of banks for withdrawal form.
     */
    public function banks(): JsonResponse
    {
        $paystack = new PaystackService();

        try {
            $banks = $paystack->listBanks();
            return response()->json(['data' => $banks]);
        } catch (\Throwable $e) {
            return response()->json(['data' => []], 500);
        }
    }

    /**
     * POST /api/wallet/resolve-account
     * Resolve bank account name from number + bank code.
     */
    public function resolveAccount(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'account_number' => 'required|string|size:10',
            'bank_code'      => 'required|string',
        ]);

        $paystack = new PaystackService();

        try {
            $result = $paystack->resolveAccount(
                accountNumber: $validated['account_number'],
                bankCode:      $validated['bank_code'],
            );

            return response()->json(['data' => $result]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Could not resolve account. Please check the details.',
            ], 422);
        }
    }
}
