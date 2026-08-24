@props(['category'])

<a href="{{ route('browse.designs', ['category' => $category->id]) }}"
   class="card-featured hover:border-coral-500 transition-colors block">
    <h3 class="heading-3 mb-2">{{ $category->name }}</h3>
    <p class="font-mono text-sm">
        {{ $category->designs_count }} {{ \Illuminate\Support\Str::plural('design', $category->designs_count) }}
    </p>
</a>
