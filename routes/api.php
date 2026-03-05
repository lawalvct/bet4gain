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

// Public statistics (no auth required)
Route::get('/stats/live', [\App\Http\Controllers\LeaderboardController::class, 'liveStats'])->name('api.stats.live');
Route::get('/leaderboard-public/{period?}', [\App\Http\Controllers\LeaderboardController::class, 'index'])->name('api.leaderboard.public');

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
        Route::get('/',                  [\App\Http\Controllers\WalletController::class, 'show'])->name('show');
        Route::get('/transactions',      [\App\Http\Controllers\WalletController::class, 'transactions'])->name('transactions');
        Route::get('/transactions/export', [\App\Http\Controllers\WalletController::class, 'exportTransactions'])->name('transactions.export');
        Route::get('/gateways',          [\App\Http\Controllers\WalletController::class, 'gateways'])->name('gateways');
        Route::get('/banks',             [\App\Http\Controllers\WalletController::class, 'banks'])->name('banks');
        Route::post('/resolve-account',  [\App\Http\Controllers\WalletController::class, 'resolveAccount'])->name('resolve-account');
        Route::post('/deposit',          [\App\Http\Controllers\WalletController::class, 'deposit'])->name('deposit');
        Route::post('/withdraw',         [\App\Http\Controllers\WalletController::class, 'withdraw'])->name('withdraw');
        Route::post('/purchase-coins',   [\App\Http\Controllers\WalletController::class, 'purchaseCoins'])->name('purchase-coins');
        Route::post('/sell-coins',       [\App\Http\Controllers\WalletController::class, 'sellCoins'])->name('sell-coins');
    });

    // Game actions — Bet placement, cashout, cancel
    Route::prefix('game')->name('api.game.')->group(function () {
        Route::post('/bet',        [\App\Http\Controllers\BetController::class, 'placeBet'])->name('bet');
        Route::post('/cashout',    [\App\Http\Controllers\BetController::class, 'cashout'])->name('cashout');
        Route::post('/cancel-bet', [\App\Http\Controllers\BetController::class, 'cancelBet'])->name('cancel-bet');
    });

    // Chat
    Route::prefix('chat')->name('api.chat.')->group(function () {
        Route::get('/messages',       [\App\Http\Controllers\ChatController::class, 'messages'])->name('messages');
        Route::get('/messages/older', [\App\Http\Controllers\ChatController::class, 'older'])->name('messages.older');
        Route::post('/messages',      [\App\Http\Controllers\ChatController::class, 'send'])->name('send');
        Route::delete('/messages/{id}', [\App\Http\Controllers\ChatController::class, 'delete'])->name('delete');
        Route::post('/mute',          [\App\Http\Controllers\ChatController::class, 'mute'])->name('mute');
        Route::post('/unmute',        [\App\Http\Controllers\ChatController::class, 'unmute'])->name('unmute');
        Route::get('/user/{id}',      [\App\Http\Controllers\ChatController::class, 'userProfile'])->name('user-profile');
    });

    // Leaderboard & Statistics
    Route::get('/leaderboard/{period?}', [\App\Http\Controllers\LeaderboardController::class, 'index'])->name('api.leaderboard');

    Route::prefix('stats')->name('api.stats.')->group(function () {
        Route::get('/me',           [\App\Http\Controllers\LeaderboardController::class, 'personalStats'])->name('me');
        Route::get('/my-bets',      [\App\Http\Controllers\LeaderboardController::class, 'myBets'])->name('my-bets');
        Route::get('/player/{id}',  [\App\Http\Controllers\LeaderboardController::class, 'playerStats'])->name('player');
    });

    // Game rounds (paginated history)
    Route::get('/game/rounds', [\App\Http\Controllers\LeaderboardController::class, 'gameHistory'])->name('api.game.rounds');

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

// ────── Payment Webhooks & Callbacks (public — no auth) ──────
Route::prefix('payments')->name('api.payments.')->group(function () {
    Route::post('/paystack/webhook', [\App\Http\Controllers\PaymentController::class, 'paystackWebhook'])->name('paystack.webhook');
    Route::post('/nomba/webhook',    [\App\Http\Controllers\PaymentController::class, 'nombaWebhook'])->name('nomba.webhook');
    Route::get('/{gateway}/callback', [\App\Http\Controllers\PaymentController::class, 'callback'])->name('callback');
});
