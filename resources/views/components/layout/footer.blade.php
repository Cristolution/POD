<footer class="bg-ink-800 text-sand-100 border-t-5 border-ink-800 mt-0">
    <div class="max-w-7xl mx-auto px-6 py-12 grid grid-cols-1 md:grid-cols-4 gap-8">
        <div>
            <div class="font-display text-2xl uppercase tracking-wider mb-4">POD<span class="text-coral-500">/</span></div>
            <p class="text-sm font-mono">{{ __('footer_tagline') }}</p>
        </div>
        <div>
            <h4 class="font-display uppercase tracking-wider mb-4">{{ __('footer_shop') }}</h4>
            <ul class="space-y-2 font-mono text-sm">
                <li><a href="{{ route('browse.designs') }}" class="hover:text-coral-500">{{ __('footer_designs') }}</a></li>
                <li><a href="{{ route('browse.categories') }}" class="hover:text-coral-500">{{ __('footer_categories') }}</a></li>
                <li><a href="{{ route('browse.designers') }}" class="hover:text-coral-500">{{ __('footer_designers') }}</a></li>
            </ul>
        </div>
        <div>
            <h4 class="font-display uppercase tracking-wider mb-4">{{ __('footer_account') }}</h4>
            <ul class="space-y-2 font-mono text-sm">
                @auth
                    <li><a href="{{ route('account.dashboard') }}" class="hover:text-coral-500">{{ __('footer_dashboard') }}</a></li>
                    <li><a href="{{ route('account.orders') }}" class="hover:text-coral-500">{{ __('footer_orders') }}</a></li>
                    <li><a href="{{ route('account.notifications') }}" class="hover:text-coral-500">{{ __('footer_notifications') }}</a></li>
                @else
                    <li><a href="{{ route('login') }}" class="hover:text-coral-500">{{ __('nav_login') }}</a></li>
                    <li><a href="{{ route('register') }}" class="hover:text-coral-500">{{ __('nav_register') }}</a></li>
                @endauth
            </ul>
        </div>
        <div>
            <h4 class="font-display uppercase tracking-wider mb-4">{{ __('footer_legal') }}</h4>
            <ul class="space-y-2 font-mono text-sm">
                <li><a href="{{ route('legal.terms') }}" class="hover:text-coral-500">{{ __('footer_terms') }}</a></li>
                <li><a href="{{ route('legal.privacy') }}" class="hover:text-coral-500">{{ __('footer_privacy') }}</a></li>
            </ul>
        </div>
    </div>
    <div class="border-t-3 border-ink-700 px-6 py-4 text-center font-mono text-xs">
        {{ __('footer_copyright', ['year' => date('Y')]) }}
    </div>
</footer>
