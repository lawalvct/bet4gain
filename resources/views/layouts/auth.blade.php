<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <!-- Enforce dark mode before CSS renders to prevent flash of light theme -->
    <script>if(localStorage.getItem('bet4gain_theme')==='light')document.documentElement.classList.remove('dark');</script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Bet4Gain') }} - @yield('title', 'Login')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @php
        $siteName = \App\Models\SiteSetting::get('site_name', config('app.name', 'Bet4Gain'));
        $siteLogo = \App\Models\SiteSetting::get('site_logo', '');
    @endphp
    <script>
        window.__BET4GAIN__ = {
            user: null,
            csrfToken: '{{ csrf_token() }}',
            appName: '{{ config("app.name") }}',
            appUrl: '{{ config("app.url") }}',
            siteName: {!! json_encode($siteName) !!},
            siteLogo: {!! json_encode($siteLogo) !!},
        };
    </script>
</head>
<body class="min-h-screen antialiased">
    <div id="app">
        @yield('content')
    </div>
</body>
</html>
