<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->cleanStaleViteHotFile();
        $this->registerFilamentActionAliases();
        $this->configureRateLimiting();
    }

    /**
     * Remove the Vite "hot" file if the dev server is no longer running.
     *
     * When `npm run dev` crashes or is stopped without cleanup, the hot file
     * persists and Laravel tries to load assets from the dead dev server,
     * resulting in a blank page. This detects that scenario and deletes the
     * stale file so Laravel falls back to the production build manifest.
     */
    private function cleanStaleViteHotFile(): void
    {
        if (! $this->app->environment('local')) {
            return;
        }

        $hotFile = public_path('hot');

        if (! file_exists($hotFile)) {
            return;
        }

        $url   = trim(file_get_contents($hotFile));
        $parts = parse_url($url);
        $host  = $parts['host'] ?? 'localhost';
        $port  = $parts['port'] ?? (($parts['scheme'] ?? 'http') === 'https' ? 443 : 80);

        // Quick TCP check — 100 ms timeout, no HTTP overhead
        $socket = @fsockopen($host, $port, $errno, $errstr, 0.1);

        if ($socket) {
            fclose($socket);  // dev server is alive, keep the hot file
        } else {
            @unlink($hotFile); // dev server is gone, remove stale file
        }
    }

    /**
     * Backward compatibility for Filament action namespaces.
     *
     * Existing resources currently reference Filament\Tables\Actions\*.
     * In Filament v5 these classes are under Filament\Actions\*.
     */
    protected function registerFilamentActionAliases(): void
    {
        $aliases = [
            'Filament\\Actions\\Action' => 'Filament\\Tables\\Actions\\Action',
            'Filament\\Actions\\ViewAction' => 'Filament\\Tables\\Actions\\ViewAction',
            'Filament\\Actions\\EditAction' => 'Filament\\Tables\\Actions\\EditAction',
            'Filament\\Actions\\CreateAction' => 'Filament\\Tables\\Actions\\CreateAction',
            'Filament\\Actions\\DeleteAction' => 'Filament\\Tables\\Actions\\DeleteAction',
            'Filament\\Actions\\BulkAction' => 'Filament\\Tables\\Actions\\BulkAction',
            'Filament\\Actions\\BulkActionGroup' => 'Filament\\Tables\\Actions\\BulkActionGroup',
            'Filament\\Actions\\DeleteBulkAction' => 'Filament\\Tables\\Actions\\DeleteBulkAction',
            'Filament\\Actions\\ForceDeleteBulkAction' => 'Filament\\Tables\\Actions\\ForceDeleteBulkAction',
            'Filament\\Actions\\RestoreBulkAction' => 'Filament\\Tables\\Actions\\RestoreBulkAction',

            // Filament v5 moved schema layout components out of Forms\Components
            'Filament\\Schemas\\Components\\Section' => 'Filament\\Forms\\Components\\Section',
            'Filament\\Schemas\\Components\\Group' => 'Filament\\Forms\\Components\\Group',
            'Filament\\Schemas\\Components\\Grid' => 'Filament\\Forms\\Components\\Grid',
            'Filament\\Schemas\\Components\\Tabs' => 'Filament\\Forms\\Components\\Tabs',
            'Filament\\Schemas\\Components\\Wizard' => 'Filament\\Forms\\Components\\Wizard',
            'Filament\\Schemas\\Components\\Fieldset' => 'Filament\\Forms\\Components\\Fieldset',
        ];

        foreach ($aliases as $newClass => $legacyClass) {
            if (class_exists($newClass) && ! class_exists($legacyClass)) {
                class_alias($newClass, $legacyClass);
            }
        }
    }

    /**
     * Configure application rate limiters (Phase 10: Security).
     */
    protected function configureRateLimiting(): void
    {
        // ── General API rate limit ──────────────────────────
        RateLimiter::for('api', function (Request $request) {
            $cfg = config('security.rate_limits.api_general');
            $key = $request->user()?->id ?: $request->ip();

            return Limit::perMinute($cfg['max_attempts'] ?? 120)
                ->by('api:' . $key)
                ->response(function () {
                    return response()->json([
                        'message' => 'Too many requests. Please slow down.',
                    ], 429);
                });
        });

        // ── Bet placement ───────────────────────────────────
        RateLimiter::for('bet-placement', function (Request $request) {
            $cfg = config('security.rate_limits.bet_placement');

            return Limit::perSecond($cfg['max_attempts'] ?? 5)
                ->by('bet:' . ($request->user()?->id ?: $request->ip()))
                ->response(function () {
                    return response()->json([
                        'message' => 'Too many bet attempts. Please wait a moment.',
                    ], 429);
                });
        });

        // ── Cashout ─────────────────────────────────────────
        RateLimiter::for('cashout', function (Request $request) {
            $cfg = config('security.rate_limits.cashout');

            return Limit::perSecond($cfg['max_attempts'] ?? 5)
                ->by('cashout:' . ($request->user()?->id ?: $request->ip()))
                ->response(function () {
                    return response()->json([
                        'message' => 'Too many cashout attempts.',
                    ], 429);
                });
        });

        // ── Chat ────────────────────────────────────────────
        RateLimiter::for('chat', function (Request $request) {
            $cfg = config('security.rate_limits.chat');
            $decay = $cfg['decay_seconds'] ?? 3;

            return [
                Limit::perSecond(1)->by('chat:' . ($request->user()?->id ?: $request->ip())),
            ];
        });

        // ── Deposit ─────────────────────────────────────────
        RateLimiter::for('deposit', function (Request $request) {
            $cfg = config('security.rate_limits.deposit');

            return Limit::perMinute($cfg['max_attempts'] ?? 5)
                ->by('deposit:' . ($request->user()?->id ?: $request->ip()))
                ->response(function () {
                    return response()->json([
                        'message' => 'Too many deposit requests. Please try again later.',
                    ], 429);
                });
        });

        // ── Withdrawal ──────────────────────────────────────
        RateLimiter::for('withdrawal', function (Request $request) {
            $cfg = config('security.rate_limits.withdrawal');
            $decay = $cfg['decay_seconds'] ?? 600;

            return Limit::perMinutes(ceil($decay / 60), $cfg['max_attempts'] ?? 3)
                ->by('withdrawal:' . ($request->user()?->id ?: $request->ip()))
                ->response(function () {
                    return response()->json([
                        'message' => 'Too many withdrawal requests. Please wait before trying again.',
                    ], 429);
                });
        });

        // ── Registration ────────────────────────────────────
        RateLimiter::for('registration', function (Request $request) {
            $cfg = config('security.rate_limits.registration');
            $decay = $cfg['decay_seconds'] ?? 600;

            return Limit::perMinutes(ceil($decay / 60), $cfg['max_attempts'] ?? 3)
                ->by('register:' . $request->ip())
                ->response(function () {
                    return response()->json([
                        'message' => 'Too many registration attempts from your location. Please try again later.',
                    ], 429);
                });
        });

        // ── Game state polling ──────────────────────────────
        RateLimiter::for('game-state', function (Request $request) {
            $cfg = config('security.rate_limits.game_state');

            return Limit::perMinute($cfg['max_attempts'] ?? 60)
                ->by('gamestate:' . ($request->user()?->id ?: $request->ip()));
        });

        // ── Webhook ─────────────────────────────────────────
        RateLimiter::for('webhook', function (Request $request) {
            $cfg = config('security.rate_limits.webhook');

            return Limit::perMinute($cfg['max_attempts'] ?? 100)
                ->by('webhook:' . $request->ip());
        });
    }
}
