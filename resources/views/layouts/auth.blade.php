<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Bet4Gain') }} - @yield('title', 'Login')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script>
        window.__BET4GAIN__ = {
            user: null,
            csrfToken: '{{ csrf_token() }}',
            appName: '{{ config("app.name") }}',
            appUrl: '{{ config("app.url") }}',
        };
    </script>
</head>
<body class="min-h-screen antialiased">
    <div id="app">
        @yield('content')
    </div>
</body>
</html>
