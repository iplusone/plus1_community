<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('title', config('app.name'))</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="portal-shell">
        <div class="portal-bg"></div>

        <header class="site-header">
            <a href="{{ route('home') }}" class="site-brand">
                <span class="site-brand__mark">P1</span>
                <span>
                    <strong>{{ config('app.name', 'Plus1 Community') }}</strong>
                    <small>Spot Portal Platform</small>
                </span>
            </a>

            <nav class="site-nav">
                <a href="{{ route('home') }}">トップ</a>
                <a href="{{ route('spots.index') }}">スポット検索</a>
            </nav>
        </header>

        <main class="page-wrap">
            @if (session('status'))
                <div class="flash-message">{{ session('status') }}</div>
            @endif

            @yield('content')
        </main>
    </body>
</html>
