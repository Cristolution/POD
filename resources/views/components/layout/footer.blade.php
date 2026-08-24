<footer class="bg-ink-800 text-sand-100 border-t-5 border-coral-500 mt-24">
    <div class="max-w-7xl mx-auto px-6 py-12 grid grid-cols-1 md:grid-cols-4 gap-8">
        <div>
            <div class="font-display text-2xl uppercase tracking-wider mb-4">POD<span class="text-coral-500">/</span></div>
            <p class="text-sm font-mono">Print on demand for independent designers.</p>
        </div>
        <div>
            <h4 class="font-display uppercase tracking-wider mb-4">Shop</h4>
            <ul class="space-y-2 font-mono text-sm">
                <li><a href="{{ route('browse.designs') }}" class="hover:text-coral-500">Designs</a></li>
                <li><a href="{{ route('browse.categories') }}" class="hover:text-coral-500">Categories</a></li>
                <li><a href="{{ route('browse.designers') }}" class="hover:text-coral-500">Designers</a></li>
            </ul>
        </div>
        <div>
            <h4 class="font-display uppercase tracking-wider mb-4">Account</h4>
            <ul class="space-y-2 font-mono text-sm">
                @auth
                    <li><a href="{{ route('account.dashboard') }}" class="hover:text-coral-500">Dashboard</a></li>
                    <li><a href="{{ route('account.orders') }}" class="hover:text-coral-500">Orders</a></li>
                    <li><a href="{{ route('account.notifications') }}" class="hover:text-coral-500">Notifications</a></li>
                @else
                    <li><a href="{{ route('login') }}" class="hover:text-coral-500">Login</a></li>
                    <li><a href="{{ route('register') }}" class="hover:text-coral-500">Register</a></li>
                @endauth
            </ul>
        </div>
        <div>
            <h4 class="font-display uppercase tracking-wider mb-4">Legal</h4>
            <ul class="space-y-2 font-mono text-sm">
                <li><a href="{{ route('legal.terms') }}" class="hover:text-coral-500">Terms</a></li>
                <li><a href="{{ route('legal.privacy') }}" class="hover:text-coral-500">Privacy</a></li>
            </ul>
        </div>
    </div>
    <div class="border-t-3 border-ink-700 px-6 py-4 text-center font-mono text-xs">
        © {{ date('Y') }} POD Platform. All rights reserved.
    </div>
</footer>