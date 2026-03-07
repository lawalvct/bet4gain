<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Phase 10: WebSocket auth hardening
| - Private channels require authentication
| - Banned users cannot join presence channels
| - Guest users limited to public game channel
|
*/

// Private user channel for personal notifications (Laravel default format)
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Private user channel for custom notifications (coin transfers, etc.)
Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Public game channel — everyone can listen
Broadcast::channel('game', function () {
    return true;
});

// Public chat channel — presence tracks online users
// Banned/muted users can still listen but cannot send messages (enforced in ChatService)
Broadcast::channel('chat', function ($user) {
    if (!$user) {
        return false;
    }

    // Banned users cannot join chat
    if ($user->is_banned) {
        return false;
    }

    return [
        'id'       => $user->id,
        'username' => $user->username,
        'avatar_url' => $user->avatar_url,
        'role'     => $user->role?->value ?? 'user',
    ];
});

// Presence channel for tracking online users
Broadcast::channel('online', function ($user) {
    if (!$user) {
        return false;
    }

    // Banned users not tracked in presence
    if ($user->is_banned) {
        return false;
    }

    return [
        'id'       => $user->id,
        'username' => $user->username,
        'avatar'   => $user->avatar,
        'is_guest' => $user->is_guest,
        'role'     => $user->role?->value ?? 'user',
    ];
});

// Private admin channel — only admin/moderator users
Broadcast::channel('admin', function ($user) {
    return $user->isAdmin() || $user->isModerator();
});
