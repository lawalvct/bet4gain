<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Bet4Gain') }} - @yield('title', 'Online Crash Game')</title>
    <meta name="description" content="@yield('description', 'Play the exciting online multiplayer crash game. Place your bets, watch the multiplier rise, and cash out before it crashes!')">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800|jetbrains-mono:400,500" rel="stylesheet" />

    <!-- Scripts & Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Pass server data to Vue -->
    @php
        $siteName = \App\Models\SiteSetting::get('site_name', config('app.name', 'Bet4Gain'));
        $siteLogo = \App\Models\SiteSetting::get('site_logo', '');
    @endphp
    <script>
        window.__BET4GAIN__ = {
            user: {!! json_encode(auth()->user()?->only(['id', 'username', 'email', 'avatar', 'role', 'is_guest', 'settings'])) !!},
            csrfToken: '{{ csrf_token() }}',
            appName: '{{ config("app.name") }}',
            appUrl: '{{ config("app.url") }}',
            siteName: {!! json_encode($siteName) !!},
            siteLogo: {!! json_encode($siteLogo) !!},
            reverb: {
                key: '{{ config("broadcasting.connections.reverb.key") }}',
                host: '{{ config("broadcasting.connections.reverb.options.host") }}',
                port: {{ config("broadcasting.connections.reverb.options.port", 8080) }},
                scheme: '{{ config("broadcasting.connections.reverb.options.scheme", "http") }}',
            },
            guestPlayEnabled: {{ config('game.guest_play_enabled') ? 'true' : 'false' }},
        };
    </script>
</head>
<body class="min-h-screen antialiased">
    <div id="app">
        @yield('content')
    </div>

    @stack('scripts')
</body>
</html>
