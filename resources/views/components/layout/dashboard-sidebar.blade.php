@props([
    'links' => [],
    'active' => null,
])

<aside class="card bg-sand-200 self-start">
    <h2 class="font-display uppercase text-sm mb-4">Navigation</h2>
    <nav>
        <ul class="space-y-1">
            @foreach ($links as $label => $url)
                @php
                    $current = $active ?? request()->url();
                    $isActive = $current === $url || request()->fullUrlIs($url);
                @endphp
                <li>
                    <a href="{{ $url }}"
                       class="block px-3 py-2 font-mono text-sm border-3 border-ink-800 {{ $isActive ? 'bg-coral-500 text-white' : 'bg-surface hover:bg-sand-100' }}">
                        {{ $label }}
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>
</aside>
