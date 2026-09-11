<aside class="card bg-sand-200">
    <h2 class="font-display uppercase tracking-wider text-lg mb-4">{{ __('account_sidebar_title') }}</h2>
    <nav class="space-y-1 font-mono text-sm">
        @php
            $links = [
                ['route' => 'account.dashboard', 'label' => __('account_sidebar_profile')],
                ['route' => 'account.addresses.index', 'label' => __('account_sidebar_addresses')],
                ['route' => 'account.orders', 'label' => __('account_sidebar_orders')],
                ['route' => 'account.wishlist', 'label' => __('account_sidebar_wishlist')],
                ['route' => 'account.notifications', 'label' => __('account_sidebar_notifications')],
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
