<?php

namespace App\Http\Controllers;

use App\Services\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function __construct(
        private readonly ChatService $chatService,
    ) {}

    /**
     * GET /api/chat/messages
     * Fetch recent chat messages.
     */
    public function messages(): JsonResponse
    {
        $limit    = config('game.chat_history_count', 50);
        $messages = $this->chatService->getRecentMessages($limit);

        return response()->json(['data' => $messages]);
    }

    /**
     * GET /api/chat/messages/older
     * Load older messages (infinite scroll).
     */
    public function older(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'before_id' => 'required|integer|min:1',
            'limit'     => 'nullable|integer|min:1|max:50',
        ]);

        $messages = $this->chatService->loadOlder(
            beforeId: (int) $validated['before_id'],
            limit:    (int) ($validated['limit'] ?? 30),
        );

        return response()->json(['data' => $messages]);
    }

    /**
     * POST /api/chat/messages
     * Send a new chat message.
     */
    public function send(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|max:500',
        ]);

        try {
            $result = $this->chatService->sendMessage(
                user:    $request->user(),
                content: $validated['message'],
            );

            return response()->json($result);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * DELETE /api/chat/messages/{id}
     * Delete a message (admin/mod only).
     */
    public function delete(Request $request, int $id): JsonResponse
    {
        try {
            $this->chatService->deleteMessage($request->user(), $id);
            return response()->json(['message' => 'Message deleted.']);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }
    }

    /**
     * POST /api/chat/mute
     * Mute a user (admin/mod only).
     */
    public function mute(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'minutes' => 'nullable|integer|min:1|max:10080', // max 7 days
        ]);

        try {
            $this->chatService->muteUser(
                moderator: $request->user(),
                userId:    (int) $validated['user_id'],
                minutes:   (int) ($validated['minutes'] ?? 10),
            );

            return response()->json(['message' => 'User muted.']);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }
    }

    /**
     * POST /api/chat/unmute
     * Unmute a user (admin/mod only).
     */
    public function unmute(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        try {
            $this->chatService->unmuteUser(
                moderator: $request->user(),
                userId:    (int) $validated['user_id'],
            );

            return response()->json(['message' => 'User unmuted.']);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }
    }

    /**
     * GET /api/chat/user/{id}
     * Get mini profile for player popover.
     */
    public function userProfile(int $id): JsonResponse
    {
        $user = \App\Models\User::select('id', 'username', 'avatar', 'role', 'created_at')
            ->withCount('bets')
            ->findOrFail($id);

        // Get win stats
        $stats = \App\Models\Bet::where('user_id', $id)
            ->selectRaw('COUNT(CASE WHEN status = "won" THEN 1 END) as wins')
            ->selectRaw('COUNT(*) as total_bets')
            ->selectRaw('MAX(cashed_out_at) as best_multiplier')
            ->first();

        return response()->json([
            'data' => [
                'id'              => $user->id,
                'username'        => $user->username,
                'avatar_url'      => $user->avatar_url,
                'role'            => $user->role->value ?? $user->role,
                'joined'          => $user->created_at->format('M Y'),
                'total_bets'      => $stats->total_bets ?? 0,
                'wins'            => $stats->wins ?? 0,
                'win_rate'        => $stats->total_bets > 0
                    ? round(($stats->wins / $stats->total_bets) * 100, 1)
                    : 0,
                'best_multiplier' => $stats->best_multiplier ?? 0,
            ],
        ]);
    }
}
