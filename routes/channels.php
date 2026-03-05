<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
*/

// Private user channel for personal notifications
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Public game channel — everyone can listen
Broadcast::channel('game', function () {
    return true;
});

// Public chat channel — everyone can listen, presence tracks online users
Broadcast::channel('chat', function ($user) {
    if ($user) {
        return [
            'id' => $user->id,
            'username' => $user->username,
            'avatar_url' => $user->avatar_url,
        ];
    }
    return false;
});
