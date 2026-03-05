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

// Auth pages (Fortify handles POST routes)
Route::get('/login', function () {
    return view('auth.login');
})->middleware('guest')->name('login');

Route::get('/register', function () {
    return view('auth.register');
})->middleware('guest')->name('register');

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
        return view('game');
    })->name('wallet');

    Route::get('/profile', function () {
        return view('game');
    })->name('profile');

    Route::get('/history', function () {
        return view('game');
    })->name('history');
});
