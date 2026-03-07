<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\CoinTransferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CoinTransferController extends Controller
{
    public function __construct(
        private CoinTransferService $transferService,
    ) {}

    /**
     * Send coins to another user.
     *
     * POST /api/wallet/transfer-coins
     */
    public function transfer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'username' => 'required|string|max:50',
            'amount'   => 'required|numeric|min:1',
            'type'     => 'nullable|string|in:gift,transfer',
            'note'     => 'nullable|string|max:200',
        ]);

        $sender = $request->user();
        $recipient = User::where('username', $validated['username'])->first();

        if (!$recipient) {
            return response()->json([
                'message' => 'User not found. Please check the username.',
            ], 404);
        }

        try {
            $transfer = $this->transferService->transfer(
                sender:    $sender,
                recipient: $recipient,
                amount:    (float) $validated['amount'],
                type:      $validated['type'] ?? 'transfer',
                note:      $validated['note'] ?? '',
            );

            // Reload balances
            $sender->load(['coinBalance', 'wallet']);

            return response()->json([
                'message'  => "Successfully sent " . number_format($transfer->net_amount, 2) . " coins to {$recipient->username}!",
                'transfer' => [
                    'id'         => $transfer->id,
                    'reference'  => $transfer->reference,
                    'amount'     => $transfer->amount,
                    'fee'        => $transfer->fee,
                    'net_amount' => $transfer->net_amount,
                    'recipient'  => $recipient->username,
                    'type'       => $transfer->type,
                ],
                'coins'  => $sender->coinBalance,
                'wallet' => $sender->wallet,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }
    }

    /**
     * Get transfer history.
     *
     * GET /api/wallet/transfers
     */
    public function history(Request $request): JsonResponse
    {
        $history = $this->transferService->getHistory($request->user());

        return response()->json($history);
    }

    /**
     * Resolve a username to verify the recipient exists.
     *
     * POST /api/wallet/resolve-user
     */
    public function resolveUser(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'username' => 'required|string|max:50',
        ]);

        $user = User::where('username', $validated['username'])
            ->where('id', '!=', $request->user()->id)
            ->first();

        if (!$user) {
            return response()->json([
                'message' => 'User not found.',
                'found'   => false,
            ], 404);
        }

        if ($user->is_guest ?? false) {
            return response()->json([
                'message' => 'Cannot send coins to guest accounts.',
                'found'   => false,
            ], 422);
        }

        return response()->json([
            'found'    => true,
            'user'     => [
                'id'       => $user->id,
                'username' => $user->username,
                'avatar'   => $user->avatar,
            ],
        ]);
    }

    /**
     * Get transfer configuration/limits for the current user.
     *
     * GET /api/wallet/transfer-config
     */
    public function config(Request $request): JsonResponse
    {
        $user = $request->user();
        $dailySent = $this->transferService->getDailySentTotal($user);
        $dailyLimit = (float) config('payment.daily_transfer_limit', 500000);

        return response()->json([
            'fee_percent'     => (float) config('payment.transfer_fee_percent', 2),
            'min_transfer'    => (float) config('payment.min_transfer', 100),
            'max_transfer'    => (float) config('payment.max_transfer', 100000),
            'daily_limit'     => $dailyLimit,
            'daily_sent'      => $dailySent,
            'daily_remaining' => max(0, $dailyLimit - $dailySent),
        ]);
    }
}
