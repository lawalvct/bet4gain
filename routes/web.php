<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Main game page (SPA entry point)
Route::get('/', function () {
    return view('game');
})->name('game');

// Auth views & POST routes are registered by Fortify (login, register, logout,
// forgot-password, reset-password, email/verify, user/confirm-password).
// View callbacks are configured in FortifyServiceProvider.

// Social Auth (OAuth)
Route::get('/auth/{provider}', [\App\Http\Controllers\SocialAuthController::class, 'redirect'])
    ->where('provider', 'google|github')
    ->name('social.redirect');

Route::get('/auth/{provider}/callback', [\App\Http\Controllers\SocialAuthController::class, 'callback'])
    ->where('provider', 'google|github')
    ->name('social.callback');

// Authenticated web routes
Route::middleware('auth')->group(function () {
    Route::get('/wallet', function () {
        return view('wallet');
    })->name('wallet');

    Route::get('/profile', function () {
        return view('profile');
    })->name('profile');

    Route::get('/history', function () {
        return view('game');
    })->name('history');
});
