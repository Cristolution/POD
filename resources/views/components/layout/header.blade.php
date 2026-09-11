<header class="bg-sand-100 border-b-5 border-ink-800 relative z-40" x-data="{ open: false }">
    <div class="max-w-7xl mx-auto px-6 py-5 flex items-center justify-between gap-4">
        <a href="{{ route('home') }}" class="font-display text-3xl uppercase tracking-wider shrink-0">
            POD<span class="text-coral-500">/</span>
        </a>

        {{-- Desktop nav (≥md): Language switcher, Cart, Account/Auth buttons inline --}}
        <nav class="hidden md:flex items-center gap-4 shrink-0">
            <x-ui.language-switcher />

            <a href="{{ route('cart.show') }}" class="nav-link relative inline-block" x-data="{ count: {{ (int) ($cartCount ?? 0) }} }">
                Cart
                <span x-show="count > 0"
                      x-text="count"
                      class="absolute -top-2 -right-3 badge-coral text-xs"></span>
            </a>

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
        </nav>

        {{-- Mobile menu trigger (<md) --}}
        <button
            type="button"
            class="md:hidden inline-flex items-center justify-center w-11 h-11 border-3 border-ink-800 bg-sand-100"
            @click="open = !open"
            :aria-expanded="open.toString()"
            aria-label="Toggle menu"
        >
            <span x-show="!open" aria-hidden="true" class="font-display text-xl leading-none">≡</span>
            <span x-show="open" aria-hidden="true" class="font-display text-xl leading-none">✕</span>
        </button>
    </div>

    {{-- Mobile menu panel (<md) --}}
    <nav
        x-show="open"
        x-cloak
        x-transition.opacity.duration.150ms
        @click.outside="open = false"
        class="md:hidden border-t-3 border-ink-800 bg-sand-100"
    >
        <div class="px-6 py-4 flex flex-col gap-3">
            <x-ui.language-switcher />

            <a href="{{ route('cart.show') }}" class="nav-link inline-flex items-center gap-2" x-data="{ count: {{ (int) ($cartCount ?? 0) }} }">
                Cart
                <span x-show="count > 0" x-text="count" class="badge-coral text-xs"></span>
            </a>

            @auth
                <a href="{{ route('account.dashboard') }}" class="nav-link">{{ auth()->user()->name }}</a>
                <form method="POST" action="{{ route('web.logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-secondary text-xs w-full">Logout</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="nav-link">Login</a>
                <a href="{{ route('register') }}" class="btn w-full">Register</a>
            @endauth
        </div>
    </nav>
</header>
