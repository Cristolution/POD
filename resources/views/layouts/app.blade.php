<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="bg-sand-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('head')
</head>
<body class="min-h-screen flex flex-col">
    <x-layout.header />

    @if (session('status'))
        <x-ui.notification-toast :message="session('status')" />
    @endif

    <main class="flex-1">
        @yield('content')
    </main>

    <x-layout.footer />

    <x-ui.cart-drawer />

    @stack('scripts')
</body>
</html>