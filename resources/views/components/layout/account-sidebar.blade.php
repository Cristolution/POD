<aside class="card bg-sand-200">
    <h2 class="font-display uppercase tracking-wider text-lg mb-4">Account</h2>
    <nav class="space-y-1 font-mono text-sm">
        @php
            $links = [
                ['route' => 'account.dashboard', 'label' => 'Profile'],
                ['route' => 'account.addresses.index', 'label' => 'Addresses'],
                ['route' => 'account.orders', 'label' => 'Orders'],
                ['route' => 'account.wishlist', 'label' => 'Wishlist'],
                ['route' => 'account.notifications', 'label' => 'Notifications'],
            ];
        @endphp

        @foreach ($links as $link)
            @php $active = request()->routeIs($link['route']); @endphp
            <a href="{{ route($link['route']) }}"
               class="block px-3 py-2 border-3 border-ink-800 {{ $active ? 'bg-coral-500 text-white' : 'bg-surface hover:bg-sand-100' }}">
                {{ $link['label'] }}
            </a>
        @endforeach
    </nav>
</aside>