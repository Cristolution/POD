<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="bg-sand-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- SEO basics — every page sets @stack('seo') to override these. --}}
    <meta name="description" content="@yield('description', 'POD marketplace connecting independent designers with independent print shops.')">
    <meta name="robots" content="@yield('robots', 'index, follow')">

    {{-- Hreflang alternates + canonical for en/ar/tr. The canonical always
         points at the English unprefixed URL via localize(null), so search
         engines consolidate ranking signals onto a single preferred page.
         `localize($locale)` handles the .localized sibling swap, preserves
         route parameters (except locale), and falls back to url('/') when
         no current route is bound.

         Guarded by the two-route convention: only emit hreflang when the
         current route has a `.localized` sibling (or is itself one). This
         skips admin routes (which have no localized variant) and avoids
         generating URLs for routes that need parameters we don't have. --}}
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

    {{-- Open Graph --}}
    <meta property="og:title" content="@yield('og_title', $title ?? config('app.name'))">
    <meta property="og:description" content="@yield('og_description', 'POD marketplace connecting independent designers with independent print shops.')">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="@yield('og_image', asset('images/og-default.png'))">
    <meta property="og:site_name" content="POD Marketplace">

    {{-- Twitter --}}
    <meta name="twitter:card" content="summary_large_image">

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