@props(['design'])

@php
    $imageUrl = null;
    if ($first = $design->media->first()) {
        $imageUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($first->file_path);
    }
    $designerName = $design->designer?->user?->name ?? 'Unknown';
@endphp

<a href="{{ route('design.show', $design) }}"
   class="card hover:border-coral-500 transition-colors group block">
    <div class="aspect-square bg-sand-200 mb-4 overflow-hidden border-3 border-ink-800">
        @if ($imageUrl)
            <img src="{{ $imageUrl }}" alt="{{ $design->title }}"
                 class="w-full h-full object-cover group-hover:scale-105 transition-transform"
                 loading="lazy">
        @else
            <div class="w-full h-full flex items-center justify-center font-display uppercase text-ink-700">
                No preview
            </div>
        @endif
    </div>
    <div class="font-display uppercase tracking-wider text-sm">{{ $design->title }}</div>
    <div class="font-mono text-xs text-ink-700 mt-1">by {{ $designerName }}</div>
    <div class="mt-3 flex items-center justify-end">
        <span class="badge">{{ $design->mappings_count }} products</span>
    </div>
</a>
