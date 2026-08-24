@props(['items' => []])

@if (count($items) > 1)
    <nav class="font-mono text-xs uppercase tracking-wider mb-6">
        @foreach ($items as $label => $href)
            @if ($loop->last)
                <span class="text-ink-800">{{ $label }}</span>
            @else
                <a href="{{ $href }}" class="text-coral-500 hover:underline">{{ $label }}</a>
                <span class="mx-2 text-ink-700">/</span>
            @endif
        @endforeach
    </nav>
@endif