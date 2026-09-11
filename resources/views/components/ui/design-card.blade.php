@props(['design'])

@php
    $imageUrl = null;
    if ($first = $design->media->first()) {
        $imageUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($first->file_path);
    }
    $designerName = $design->designer?->user?->name ?? __('unknown');

    $isFavourited = auth()->check()
        ? (auth()->user()->wishlist?->hasDesign($design->id) ?? false)
        : false;

    $minPrice = $design->min_price ?? $design->mappings->min('final_price');
    $maxPrice = $design->max_price ?? $design->mappings->max('final_price');

    $productTypes = $design->mappings
        ->map(fn ($m) => $m->productTemplate?->type)
        ->filter()
        ->unique()
        ->values();
    $visibleTypes = $productTypes->take(3);
    $extraTypeCount = max(0, $productTypes->count() - $visibleTypes->count());

    $variantCount = $design->mappings
        ->sum(fn ($m) => $m->productTemplate?->variants?->where('is_active', true)->count() ?? 0);

    $priceLabel = $minPrice === null
        ? null
        : ($minPrice == $maxPrice
            ? '$'.number_format((float) $minPrice, 2)
            : '$'.number_format((float) $minPrice, 2).' – $'.number_format((float) $maxPrice, 2));
@endphp

{{-- Card wrapper. The <a> wraps the visual content (image + body + footer);
     the heart form is rendered OUTSIDE the link with absolute positioning
     so nested-form HTML is avoided. The link still receives clicks anywhere
     except on the heart. --}}
<div class="relative bg-surface border-3 border-ink-800 hover:border-coral-500 transition-colors duration-150 group">

    {{-- Favourite (heart) button — absolute over the image, separate from the <a> --}}
    @auth
        <form method="POST" action="{{ $isFavourited ? route('design.wishlist.destroy', $design) : route('design.wishlist.store', $design) }}"
              class="absolute bottom-3 right-3 z-10"
              onclick="event.stopPropagation();">
            @csrf
            @if ($isFavourited)
                @method('DELETE')
                <button type="submit"
                        class="bg-surface border-3 border-ink-800 w-10 h-10 flex items-center justify-center hover:bg-coral-500 hover:text-white transition-colors text-coral-500"
                        title="{{ __('design_card_remove_wishlist') }}" aria-label="{{ __('design_card_remove_wishlist') }}">
                    ♥
                </button>
            @else
                <button type="submit"
                        class="bg-surface border-3 border-ink-800 w-10 h-10 flex items-center justify-center hover:bg-coral-500 hover:text-white transition-colors"
                        title="{{ __('design_card_save_wishlist') }}" aria-label="{{ __('design_card_save_wishlist') }}">
                    ♡
                </button>
            @endif
        </form>
    @endauth

    <a href="{{ route('design.show', $design) }}" class="block">

        {{-- Image --}}
        <div class="design-card-image">
            @if ($imageUrl)
                <img src="{{ $imageUrl }}" alt="{{ $design->title }}"
                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                     loading="lazy">
            @else
                <div class="w-full h-full flex items-center justify-center font-display uppercase text-ink-700 tracking-widest">
                    {{ __('design_card_no_preview') }}
                </div>
            @endif

            @if ($priceLabel)
                <div class="absolute top-3 left-3 bg-coral-500 text-white border-3 border-ink-800 px-2.5 py-1 font-display tracking-wider text-sm">
                    {{ $priceLabel }}
                </div>
            @endif

            @if (($design->relationLoaded('approvedReviews') ? $design->approvedReviews->count() : null) ?? 0)
                @php $reviewCount = $design->relationLoaded('approvedReviews') ? $design->approvedReviews->count() : 0; @endphp
                <div class="absolute top-3 right-3 bg-sand-100 text-ink-800 border-3 border-ink-800 px-2.5 py-1 font-mono text-xs">
                    <span class="text-coral-500 font-bold">★ {{ number_format((float) $design->averageRating(), 1) }}</span>
                    <span class="opacity-70">({{ $reviewCount }})</span>
                </div>
            @endif
        </div>

        {{-- Body --}}
        <div class="p-3">
            <h3 class="font-display uppercase tracking-wider text-base leading-tight line-clamp-2">
                {{ $design->title }}
            </h3>

            <div class="mt-1 font-mono text-[10px] text-ink-700 uppercase tracking-widest">
                {{ __('design_card_by') }} <span class="text-ink-800">{{ $designerName }}</span>
            </div>

            @if ($visibleTypes->isNotEmpty())
                <div class="mt-2 flex flex-wrap gap-1">
                    @foreach ($visibleTypes as $type)
                        <span class="font-mono text-[10px] uppercase tracking-widest px-1.5 py-0.5 border-3 border-ink-800 bg-sand-100">
                            {{ str_replace('-', ' ', $type) }}
                        </span>
                    @endforeach
                    @if ($extraTypeCount > 0)
                        <span class="font-mono text-[10px] uppercase tracking-widest px-1.5 py-0.5 border-3 border-ink-800 bg-ink-800 text-sand-100">
                            +{{ $extraTypeCount }}
                        </span>
                    @endif
                </div>
            @endif
        </div>

        {{-- Footer band --}}
        <div class="grid grid-cols-2 border-t-3 border-ink-800 font-mono text-xs uppercase tracking-widest">
            <div class="px-2 py-1.5 border-r-3 border-ink-800 text-center">
                <span class="font-display text-sm">{{ $design->mappings_count }}</span>
                <span class="block text-[9px] opacity-70">{{ \Illuminate\Support\Str::plural('product', $design->mappings_count) }}</span>
            </div>
            <div class="px-2 py-1.5 text-center">
                <span class="font-display text-sm">{{ $variantCount }}</span>
                <span class="block text-[9px] opacity-70">{{ \Illuminate\Support\Str::plural('variant', $variantCount) }}</span>
            </div>
        </div>
    </a>
</div>
