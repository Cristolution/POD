@props(['compact' => false])
@php
    $locales = ['en' => 'EN', 'ar' => 'AR', 'tr' => 'TR'];
    $current = app()->getLocale();
    // The two-route convention only covers public site routes. Admin routes
    // (filament.*) and any other unbound routes would blow up `route()` when
    // we tried to rewrite them — guard with the same has-localized-sibling
    // check that layouts/app.blade.php uses for hreflang.
    $currentRouteName = request()->route()?->getName();
    $hasLocalizedRoute = $currentRouteName !== null && (
        str_ends_with($currentRouteName, '.localized')
        || \Illuminate\Support\Facades\Route::getRoutes()->getByName($currentRouteName.'.localized') !== null
    );
@endphp
@if ($hasLocalizedRoute)
<div class="flex items-center gap-1 {{ $compact ? 'text-xs' : 'text-sm' }} font-mono">
@foreach ($locales as $code => $label)
@php
    $isCurrent = $code === $current;
    // Same-locale (active) link uses LocalizedUrl::route() so the URL
    // follows the current request's locale; cross-locale links use
    // localize($code) to rewrite into the target locale on the same page.
    // We forward the current route's parameters because LocalizedUrl::route()
    // does NOT auto-resolve them from the bound request (unlike localize()).
    $sameLocaleParameters = request()->route()?->parameters() ?? [];
    unset($sameLocaleParameters['locale']);
    $href = $isCurrent
        ? \App\Support\LocalizedUrl::route($currentRouteName, $sameLocaleParameters)
        : localize($code);
@endphp
<a href="{{ $href }}"@class(['border-2 px-2 py-0.5', $isCurrent ? 'border-black bg-black text-white' : 'border-black hover:bg-black hover:text-white']) hreflang="{{ $code }}" lang="{{ $code }}" dir="{{ $code === 'ar' ? 'rtl' : 'ltr' }}" aria-current="{{ $isCurrent ? 'true' : 'false' }}">{{ $label }}</a>
@endforeach
</div>
@endif