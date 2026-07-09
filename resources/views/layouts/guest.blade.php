<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sign in') — {{ $branding->orgName() }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            @foreach ($branding->cssVariables() as $var => $value)
            {{ $var }}: {{ $value }};
            @endforeach
        }
    </style>
    @stack('head')
</head>
<body class="min-h-screen bg-surface font-sans text-on-surface antialiased">
    <div class="flex min-h-screen items-center justify-center p-3 sm:p-4">
        @yield('content')
    </div>
    @stack('scripts')
</body>
</html>
