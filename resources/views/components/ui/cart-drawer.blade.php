@props([])

<div
    x-data="{ open: false }"
    @open-cart-drawer.window="open = true"
    class="fixed inset-0 z-50 pointer-events-none"
    :class="{ 'pointer-events-auto': open }"
>
    <div
        x-show="open"
        x-transition.opacity
        @click="open = false"
        class="absolute inset-0 bg-ink-900/50"
    ></div>

    <div
        x-show="open"
        x-transition
        class="absolute top-0 right-0 h-full w-full max-w-md bg-surface border-l-5 border-ink-800 overflow-y-auto"
    >
        <div class="p-6 border-b-3 border-ink-800 flex items-center justify-between">
            <h2 class="heading-3">{{ __('nav_cart') }}</h2>
            <button @click="open = false" class="btn-secondary btn text-xs">{{ __('cart_drawer_close') }}</button>
        </div>
        <div class="p-6">
            <p class="font-mono text-sm">
                {{ __('cart_drawer_full_page') }}
                <a href="{{ route('cart.show') }}" class="text-coral-500 hover:underline">/cart</a>.
            </p>
        </div>
    </div>
</div>
