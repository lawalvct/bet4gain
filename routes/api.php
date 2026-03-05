<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| These routes are loaded by the RouteServiceProvider and are assigned
| the "api" middleware group. They are prefixed with /api.
*/

// Public API routes
Route::get('/settings', function () {
    $settings = \App\Models\SiteSetting::getGroup('general');
    $gameSettings = \App\Models\SiteSetting::getGroup('game');
    $appearanceSettings = \App\Models\SiteSetting::getGroup('appearance');

    return response()->json([
        'data' => array_merge($settings, $gameSettings, $appearanceSettings),
    ]);
})->name('api.settings');

// Game public routes
Route::prefix('game')->name('api.game.')->group(function () {
    Route::get('/history',             [\App\Http\Controllers\GameController::class, 'history'])->name('history');
    Route::get('/state',               [\App\Http\Controllers\GameController::class, 'state'])->name('state');
    Route::get('/live-bets',           [\App\Http\Controllers\GameController::class, 'liveBets'])->name('live-bets');
    Route::get('/round/{id}',          [\App\Http\Controllers\GameController::class, 'round'])->name('round');
    Route::get('/round/{id}/bets',     [\App\Http\Controllers\GameController::class, 'roundBets'])->name('round.bets');
    Route::post('/verify',             [\App\Http\Controllers\GameController::class, 'verify'])->name('verify');
});

// Guest session
Route::post('/guest', [\App\Http\Controllers\GuestController::class, 'create'])->name('api.guest');
Route::post('/guest/resume', [\App\Http\Controllers\GuestController::class, 'resume'])->name('api.guest.resume');

// Authenticated API routes
Route::middleware('auth:sanctum')->group(function () {
    // User
    Route::get('/user', function (\Illuminate\Http\Request $request) {
        $user = $request->user()->load(['wallet', 'coinBalance']);
        return response()->json($user);
    })->name('api.user');

    Route::put('/user/profile', function (\Illuminate\Http\Request $request) {
        // Profile info update is handled by Fortify at PUT /user/profile-information
        // Password update is handled by Fortify at PUT /user/password
        return response()->json(['message' => 'Use PUT /user/profile-information or PUT /user/password'], 301);
    })->name('api.user.profile');

    // Avatar
    Route::post('/user/avatar', [\App\Http\Controllers\ProfileController::class, 'uploadAvatar'])->name('api.user.avatar');
    Route::delete('/user/avatar', [\App\Http\Controllers\ProfileController::class, 'removeAvatar'])->name('api.user.avatar.remove');

    // User settings/preferences
    Route::put('/user/settings', [\App\Http\Controllers\ProfileController::class, 'updateSettings'])->name('api.user.settings');

    // Wallet
    Route::prefix('wallet')->name('api.wallet.')->group(function () {
        Route::get('/', function (\Illuminate\Http\Request $request) {
            return response()->json([
                'wallet' => $request->user()->wallet,
                'coins' => $request->user()->coinBalance,
            ]);
        })->name('show');

        Route::get('/transactions', function (\Illuminate\Http\Request $request) {
            $transactions = $request->user()->transactions()
                ->orderByDesc('created_at')
                ->paginate(20);
            return response()->json($transactions);
        })->name('transactions');

        // Deposit / Withdraw / Purchase Coins — Placeholder
        Route::post('/deposit', function () {
            return response()->json(['message' => 'Not implemented yet'], 501);
        })->name('deposit');

        Route::post('/withdraw', function () {
            return response()->json(['message' => 'Not implemented yet'], 501);
        })->name('withdraw');

        Route::post('/purchase-coins', function () {
            return response()->json(['message' => 'Not implemented yet'], 501);
        })->name('purchase-coins');
    });

    // Game actions — Placeholder (Phase 3)
    Route::prefix('game')->name('api.game.')->group(function () {
        Route::post('/bet', function () {
            return response()->json(['message' => 'Not implemented yet'], 501);
        })->name('bet');

        Route::post('/cashout', function () {
            return response()->json(['message' => 'Not implemented yet'], 501);
        })->name('cashout');

        Route::post('/cancel-bet', function () {
            return response()->json(['message' => 'Not implemented yet'], 501);
        })->name('cancel-bet');
    });

    // Chat
    Route::prefix('chat')->name('api.chat.')->group(function () {
        Route::get('/messages', function () {
            $messages = \App\Models\ChatMessage::visible()
                ->with('user:id,username,avatar')
                ->orderByDesc('id')
                ->limit(config('game.chat.max_messages', 100))
                ->get()
                ->reverse()
                ->values();

            return response()->json(['data' => $messages]);
        })->name('messages');

        Route::post('/messages', function (\Illuminate\Http\Request $request) {
            $request->validate([
                'content' => 'required|string|max:500',
            ]);

            $message = $request->user()->chatMessages()->create([
                'content' => $request->content,
                'type' => \App\Enums\ChatMessageType::Text,
            ]);

            $message->load('user:id,username,avatar');

            // Broadcast will be handled in Phase 5
            return response()->json(['data' => $message]);
        })->name('send');
    });

    // Leaderboard
    Route::get('/leaderboard/{period?}', function (string $period = 'daily') {
        $validPeriods = ['daily', 'weekly', 'monthly', 'all_time'];
        if (!in_array($period, $validPeriods)) {
            $period = 'daily';
        }

        $entries = \App\Models\LeaderboardEntry::where('period', $period)
            ->with('user:id,username,avatar')
            ->orderByDesc('total_profit')
            ->limit(20)
            ->get();

        return response()->json(['data' => $entries]);
    })->name('api.leaderboard');

    // Ads
    Route::prefix('ads')->name('api.ads.')->group(function () {
        Route::post('/{ad}/impression', function (\App\Models\Advertisement $ad) {
            $ad->increment('impressions');
            return response()->json(['ok' => true]);
        })->name('impression');

        Route::post('/{ad}/click', function (\App\Models\Advertisement $ad) {
            $ad->increment('clicks');
            return response()->json(['ok' => true]);
        })->name('click');
    });
});
