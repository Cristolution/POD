@props(['designer'])

@php
    $name = $designer->user?->name ?? __('unknown');
    $email = trim(strtolower((string) $designer->user?->email));
    $gravatarUrl = $email
        ? 'https://www.gravatar.com/avatar/'.md5($email).'?d=identicon&s=128'
        : null;
@endphp

<a href="{{ route('designer.show', $designer) }}"
   class="card hover:border-coral-500  p-3 transition-colors block">
    <div class="flex items-center gap-4 mb-3">
        <div class="w-16 h-16 bg-sand-200 border-3 border-ink-800 overflow-hidden flex-shrink-0">
            @if ($gravatarUrl)
                <img src="{{ $gravatarUrl }}" alt="" class="w-full h-full object-cover"
                     loading="lazy">
            @endif
        </div>
        <div class="min-w-0">
            <div class="font-display uppercase tracking-wider truncate">{{ $name }}</div>
            <div class="font-mono text-xs mt-1">{{ $designer->published_designs_count }} {{ __('designer_card_published_designs') }}</div>
        </div>
    </div>
    @if ($designer->bio)
        <p class="font-mono text-sm text-ink-700 line-clamp-2">{{ $designer->bio }}</p>
    @endif
</a>
