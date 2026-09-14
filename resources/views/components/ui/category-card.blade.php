@props(['category'])

@php
    // `total_designs_count` is splatted by HomeController and counts every
    // published design in this category AND its descendants (so the badge
    // matches what the user sees when they click through — the browse page
    // expands the filter to include sub-categories). Falls back to the
    // direct `designs_count` for callers that don't pre-compute it.
    $count = (int) ($category->total_designs_count ?? $category->designs_count ?? 0);
@endphp

<a href="{{ route('browse.designs', ['category' => $category->id]) }}"
   class="card-featured hover:border-coral-500 transition-colors block">
    <h3 class="heading-3 mb-2">{{ $category->name }}</h3>
    <p class="font-mono text-sm">
        {{ $count }} {{ \Illuminate\Support\Str::plural('design', $count) }}
    </p>
</a>
