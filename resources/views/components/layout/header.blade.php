<header class="bg-sand-100 border-b-5 border-ink-800">
    <div class="max-w-7xl mx-auto px-6 py-5 flex items-center justify-between">
        <a href="{{ route('home') }}" class="font-display text-3xl uppercase tracking-wider">
            POD<span class="text-coral-500">/</span>
        </a>

        <div class="flex items-center gap-4">
            <a href="{{ route('cart.show') }}" class="nav-link relative" x-data="{ count: {{ (int) ($cartCount ?? 0) }} }">
                Cart
                <span x-show="count > 0"
                      x-text="count"
                      class="absolute -top-2 -right-4 badge-coral text-xs"></span>
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
        </div>
    </div>
</header>