<?php

namespace App\Services;

use App\Enums\ChatMessageType;
use App\Events\ChatMessageDeleted;
use App\Events\ChatMessageSent;
use App\Models\ChatMessage;
use App\Models\CoinBalance;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ChatService
{
    // ────── Profanity Filter Word List ──────

    private const PROFANITY_WORDS = [
        'fuck', 'shit', 'bitch', 'asshole', 'bastard', 'damn', 'cunt',
        'dick', 'nigger', 'nigga', 'faggot', 'retard', 'slut', 'whore',
        'pussy', 'cock', 'twat', 'wanker', 'prick',
    ];

    /**
     * Send a chat message with rate limiting, profanity filter, and command parsing.
     */
    public function sendMessage(User $user, string $content): array
    {
        // Validate chat is enabled
        if (!config('game.chat_enabled', true)) {
            throw new \RuntimeException('Chat is currently disabled.');
        }

        // Check ban
        if ($user->is_banned) {
            throw new \RuntimeException('You are banned from the platform.');
        }

        // Check mute
        if ($user->muted_until && now()->lt($user->muted_until)) {
            $remaining = now()->diffForHumans($user->muted_until, ['syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE]);
            throw new \RuntimeException("You are muted for {$remaining}.");
        }

        // Rate limiting
        $this->checkRateLimit($user);

        // Trim and validate
        $content = trim($content);
        $maxLength = config('game.chat_max_length', 200);

        if (empty($content)) {
            throw new \RuntimeException('Message cannot be empty.');
        }

        if (mb_strlen($content) > $maxLength) {
            $content = mb_substr($content, 0, $maxLength);
        }

        // Check for commands
        if (str_starts_with($content, '/')) {
            return $this->handleCommand($user, $content);
        }

        // Apply profanity filter
        $content = $this->filterProfanity($content);

        // Create message
        $message = ChatMessage::create([
            'user_id' => $user->id,
            'message' => $content,
            'type'    => ChatMessageType::Text,
        ]);

        $message->load('user:id,username,avatar');

        $broadcastData = $message->toBroadcastArray();

        // Broadcast to all
        event(new ChatMessageSent($broadcastData));

        // Update rate limit
        $this->setRateLimit($user);

        return [
            'message' => $broadcastData,
            'sent'    => true,
        ];
    }

    /**
     * Delete a message (admin/mod only).
     */
    public function deleteMessage(User $moderator, int $messageId): void
    {
        if (!$moderator->isModerator()) {
            throw new \RuntimeException('You do not have permission to delete messages.');
        }

        $message = ChatMessage::findOrFail($messageId);

        $message->update([
            'is_deleted' => true,
            'deleted_by' => $moderator->id,
        ]);

        event(new ChatMessageDeleted($messageId));
    }

    /**
     * Mute a user for a duration.
     */
    public function muteUser(User $moderator, int $userId, int $minutes = 10): void
    {
        if (!$moderator->isModerator()) {
            throw new \RuntimeException('You do not have permission to mute users.');
        }

        $target = User::findOrFail($userId);

        if ($target->isAdmin()) {
            throw new \RuntimeException('Cannot mute an admin.');
        }

        $target->update([
            'muted_until' => now()->addMinutes($minutes),
        ]);

        // Send system message
        $this->createSystemMessage("{$target->username} has been muted for {$minutes} minutes.");
    }

    /**
     * Unmute a user.
     */
    public function unmuteUser(User $moderator, int $userId): void
    {
        if (!$moderator->isModerator()) {
            throw new \RuntimeException('You do not have permission to unmute users.');
        }

        $target = User::findOrFail($userId);
        $target->update(['muted_until' => null]);
    }

    /**
     * Create and broadcast a system message (for win announcements, rain, etc.).
     */
    public function createSystemMessage(string $text, ?int $userId = null): ChatMessage
    {
        $message = ChatMessage::create([
            'user_id' => $userId ?? User::where('role', 'admin')->first()?->id ?? 1,
            'message' => $text,
            'type'    => ChatMessageType::System,
        ]);

        $broadcastData = [
            'id'         => $message->id,
            'user_id'    => $message->user_id,
            'username'   => 'System',
            'avatar'     => null,
            'message'    => $text,
            'type'       => 'system',
            'created_at' => $message->created_at->toISOString(),
        ];

        event(new ChatMessageSent($broadcastData));

        return $message;
    }

    /**
     * Announce a big win in chat.
     */
    public function announceWin(string $username, float $amount, float $multiplier, float $payout): void
    {
        $minMultiplier = config('game.win_announcement_min_multiplier', 10);

        if ($multiplier < $minMultiplier) {
            return;
        }

        $formattedPayout = number_format($payout, 2);
        $formattedMultiplier = number_format($multiplier, 2);

        $this->createSystemMessage(
            "🏆 {$username} cashed out at {$formattedMultiplier}x and won {$formattedPayout} coins!"
        );
    }

    /**
     * Load older messages (before a given ID) for pagination.
     */
    public function loadOlder(?int $beforeId = null, int $limit = 30): array
    {
        $query = ChatMessage::visible()
            ->with('user:id,username,avatar')
            ->orderByDesc('id')
            ->limit($limit);

        if ($beforeId) {
            $query->where('id', '<', $beforeId);
        }

        return $query->get()->reverse()->values()->toArray();
    }

    /**
     * Get recent messages.
     */
    public function getRecentMessages(int $limit = 50): array
    {
        return ChatMessage::visible()
            ->with('user:id,username,avatar')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values()
            ->toArray();
    }

    // ────── Chat Commands ──────

    private function handleCommand(User $user, string $input): array
    {
        $parts   = explode(' ', $input, 3);
        $command = strtolower($parts[0]);

        return match ($command) {
            '/rain'  => $this->handleRainCommand($user, $parts),
            '/help'  => $this->handleHelpCommand(),
            default  => throw new \RuntimeException("Unknown command: {$command}. Type /help for available commands."),
        };
    }

    /**
     * /rain <amount> — Distribute coins randomly to online users.
     */
    private function handleRainCommand(User $user, array $parts): array
    {
        $amount = (float) ($parts[1] ?? 0);

        if ($amount < 10) {
            throw new \RuntimeException('Rain amount must be at least 10 coins.');
        }

        if ($amount > 100000) {
            throw new \RuntimeException('Rain amount cannot exceed 100,000 coins.');
        }

        // Check user has enough coins
        $coinBalance = $user->coinBalance;
        if (!$coinBalance || $coinBalance->balance < $amount) {
            throw new \RuntimeException('Insufficient coin balance for rain.');
        }

        // Get online user IDs from presence cache (or all recent active users)
        $onlineUserIds = Cache::get('online_user_ids', []);

        // Filter out the sender and limit recipients
        $recipients = collect($onlineUserIds)
            ->filter(fn($id) => $id !== $user->id)
            ->values();

        if ($recipients->isEmpty()) {
            throw new \RuntimeException('No other users online to rain on.');
        }

        $maxRecipients = min($recipients->count(), 20);
        $recipients    = $recipients->random($maxRecipients);
        $perUser       = round($amount / $recipients->count(), 4);

        DB::transaction(function () use ($user, $recipients, $perUser, $amount) {
            // Deduct from sender
            CoinBalance::where('user_id', $user->id)
                ->where('balance', '>=', $amount)
                ->decrement('balance', $amount);

            // Credit each recipient
            foreach ($recipients as $recipientId) {
                CoinBalance::where('user_id', $recipientId)
                    ->increment('balance', $perUser);
            }
        });

        $formattedAmount = number_format($amount, 2);
        $this->createSystemMessage(
            "🌧️ {$user->username} made it rain {$formattedAmount} coins on {$recipients->count()} users! ({$perUser} each)"
        );

        $this->setRateLimit($user);

        return [
            'message' => null,
            'sent'    => true,
            'system'  => "Rain successful! {$formattedAmount} coins distributed to {$recipients->count()} users.",
        ];
    }

    private function handleHelpCommand(): array
    {
        return [
            'message' => null,
            'sent'    => false,
            'system'  => "Available commands:\n/rain <amount> — Rain coins on online users\n/help — Show this help",
        ];
    }

    // ────── Rate Limiting ──────

    private function checkRateLimit(User $user): void
    {
        $cacheKey  = "chat_rate_limit:{$user->id}";
        $lastSent  = Cache::get($cacheKey);
        $rateLimit = config('game.chat_rate_limit', 3);

        if ($lastSent && now()->diffInSeconds($lastSent) < $rateLimit) {
            $wait = $rateLimit - now()->diffInSeconds($lastSent);
            throw new \RuntimeException("Please wait {$wait} seconds before sending another message.");
        }
    }

    private function setRateLimit(User $user): void
    {
        $rateLimit = config('game.chat_rate_limit', 3);
        Cache::put("chat_rate_limit:{$user->id}", now(), $rateLimit + 1);
    }

    // ────── Profanity Filter ──────

    private function filterProfanity(string $content): string
    {
        // Get custom word list from site settings if available
        $customWords = [];
        try {
            $setting = \App\Models\SiteSetting::where('key', 'profanity_filter_words')->first();
            if ($setting && $setting->value) {
                $customWords = array_map('trim', explode(',', $setting->value));
            }
        } catch (\Throwable) {
            // SiteSetting may not exist yet
        }

        $allWords = array_merge(self::PROFANITY_WORDS, array_filter($customWords));

        foreach ($allWords as $word) {
            if (empty($word)) continue;
            $pattern = '/\b' . preg_quote($word, '/') . '\b/iu';
            $replacement = str_repeat('*', mb_strlen($word));
            $content = preg_replace($pattern, $replacement, $content);
        }

        return $content;
    }
}
