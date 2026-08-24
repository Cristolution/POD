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
    {{-- Marketing header: same chrome as the main app but stripped of the
         cart link and any notification surface so legal/marketing pages stay
         visually quieter than the storefront. --}}
    <header class="bg-sand-100 border-b-5 border-ink-800">
        <div class="max-w-7xl mx-auto px-6 py-5 flex items-center justify-between">
            <a href="{{ route('home') }}" class="font-display text-3xl uppercase tracking-wider">
                POD<span class="text-coral-500">/</span>
            </a>

            <div class="flex items-center gap-4">
                @auth
                    <a href="{{ route('account.dashboard') }}" class="nav-link">{{ auth()->user()->name }}</a>
                    <form method="POST" action="{{ route('web.logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-secondary text-xs">Logout</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="nav-link">Login</a>
                    <a href="{{ route('register') }}" class="btn">Register</a>
                @endauth
            </div>
        </div>
    </header>

    <main class="flex-1">
        @yield('content')
    </main>

    <x-layout.footer />

    @stack('scripts')
</body>
</html>
