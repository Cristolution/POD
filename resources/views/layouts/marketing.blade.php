<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      dir="{{ app()->isLocale('ar') ? 'rtl' : 'ltr' }}"
      class="bg-sand-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name') }}</title>

    {{-- Hreflang alternates + canonical for en/ar/tr. The canonical always
         points at the English unprefixed URL via localize(null), so search
         engines consolidate ranking signals onto a single preferred page.
         `localize($locale)` handles the .localized sibling swap, preserves
         route parameters (except locale), and falls back to url('/') when
         no current route is bound.

         Guarded by the two-route convention: only emit hreflang when the
         current route has a `.localized` sibling (or is itself one). --}}
    @php
        $currentRoute = request()->route();
        $currentRouteName = $currentRoute?->getName();
        $hasLocalizedRoute = $currentRouteName !== null && (
            str_ends_with($currentRouteName, '.localized')
            || \Illuminate\Support\Facades\Route::getRoutes()->getByName($currentRouteName.'.localized') !== null
        );
    @endphp
    @if ($hasLocalizedRoute)
        @foreach (['en', 'ar', 'tr'] as $hreflangLocale)
            <link rel="alternate" hreflang="{{ $hreflangLocale }}" href="{{ localize($hreflangLocale) }}" />
        @endforeach
        <link rel="canonical" href="{{ localize(null) }}">
    @endif

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
                        <button type="submit" class="btn btn-secondary text-xs">{{ __('nav_logout') }}</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="nav-link">{{ __('nav_login') }}</a>
                    <a href="{{ route('register') }}" class="btn">{{ __('nav_register') }}</a>
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
