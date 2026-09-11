@extends('layouts.app', ['title' => $design->title . ' — POD'])

@section('description', $design->title . ' by ' . ($design->designer?->user?->name ?? 'unknown designer') . ' — POD Marketplace.')
@section('og_title', $design->title . ' — POD Marketplace')
@section('og_type', 'product')

@push('head')
    @php
        $primaryImage = $defaultImageUrl ?? ($productMockups->isNotEmpty() ? \Illuminate\Support\Facades\Storage::disk('public')->url($productMockups->first()->file_path) : null);
        $minPrice = $design->mappings->min('final_price');
        $avgRating = $design->averageRating();
        $reviewCount = $design->approvedReviews->count();
        $jsonLd = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $design->title,
            'description' => ($design->title) . ' by ' . ($design->designer?->user?->name ?? 'Unknown') . ' — printed on demand.',
            'url' => route('design.show', $design),
            'image' => $primaryImage ? asset($primaryImage) : null,
            'brand' => ['@type' => 'Brand', 'name' => 'POD Marketplace'],
            'creator' => ['@type' => 'Person', 'name' => $design->designer?->user?->name ?? 'Unknown'],
            'offers' => $design->mappings->map(fn ($m) => [
                '@type' => 'Offer',
                'priceCurrency' => 'USD',
                'price' => number_format((float) $m->final_price, 2, '.', ''),
                'availability' => 'https://schema.org/InStock',
                'url' => route('design.show', $design),
            ])->all(),
        ];
        if ($avgRating !== null && $reviewCount > 0) {
            $jsonLd['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => $avgRating,
                'reviewCount' => $reviewCount,
            ];
        }
        $jsonLd = array_filter($jsonLd, fn ($v) => $v !== null && $v !== []);
    @endphp
    @if ($primaryImage)
        @section('og_image', $primaryImage)
    @endif
    <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES) !!}</script>
@endpush

@section('content')
    @php
        $defaultImageUrl = $defaultMockup
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($defaultMockup->file_path)
            : null;
        $designerName = $design->designer?->user?->name ?? 'Unknown';
    @endphp

    <section class="max-w-7xl mx-auto px-6 py-12">
        <x-layout.breadcrumbs :items="[
            'Home' => route('home'),
            'Designs' => route('browse.designs'),
            $design->title => route('design.show', $design),
        ]" />

        {{-- SCENARIO A: design has a default mockup → show hero + product cards --}}
        @if ($defaultImageUrl || $mappings->isNotEmpty())
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                {{-- HERO: design preview --}}
                <div>
                    <div class="border-5 border-ink-800 bg-surface aspect-square overflow-hidden">
                        @if ($defaultImageUrl)
                            <img src="{{ $defaultImageUrl }}" alt="{{ $design->title }}" class="w-full h-full object-cover">
                        @else
                            {{-- Scenario: design has mappings but no main mockup (shouldn't happen post-upload, but handle) --}}
                            <div class="w-full h-full bg-sand-200 flex items-center justify-center font-display uppercase">
                                No preview
                            </div>
                        @endif
                    </div>
                    @if ($design->designer)
                        <p class="font-mono text-sm mt-4 text-ink-700">
                            Designed by
                            <a href="{{ route('designer.show', $design->designer) }}" class="text-coral-500 hover:underline">
                                {{ $designerName }}
                            </a>
                        </p>
                    @endif
                </div>

                {{-- COL 2: title + per-product purchase cards --}}
                <div>
                    @if ($design->category)
                        <span class="badge">{{ $design->category->name }}</span>
                    @else
                        <span class="badge">Uncategorized</span>
                    @endif
                    <h1 class="heading-1 mt-4 mb-2">{{ $design->title }}</h1>

                    {{-- SCENARIO B: design has tags --}}
                    @if ($design->tags->isNotEmpty())
                        <div class="flex flex-wrap gap-2 mb-6">
                            @foreach ($design->tags as $tag)
                                <span class="badge bg-sand-200 text-ink-800">#{{ $tag->name }}</span>
                            @endforeach
                        </div>
                    @endif

                    {{-- SCENARIO C: design has mappings → show product cards --}}
                    @if ($mappings->isNotEmpty())
                        <div class="space-y-6">
                            @foreach ($mappings as $mapping)
                                @php
                                    $productTemplate = $mapping->productTemplate;
                                    $productImageUrl = $productMockups->has($productTemplate->id)
                                        ? \Illuminate\Support\Facades\Storage::disk('public')->url($productMockups->get($productTemplate->id)->file_path)
                                        : $defaultImageUrl;
                                @endphp
                                <div class="card border-3 border-ink-800">
                                    {{-- Optional per-product mockup preview; falls back to default --}}
                                    @if ($productImageUrl)
                                        <div class="border-3 border-ink-800 bg-sand-100 aspect-square mb-4 overflow-hidden">
                                            <img src="{{ $productImageUrl }}" alt="{{ $design->title }} on {{ $productTemplate->type }}" class="w-full h-full object-cover">
                                        </div>
                                    @endif

                                    <div class="flex items-baseline justify-between mb-3">
                                        <h3 class="heading-3">{{ ucwords(str_replace('-', ' ', $productTemplate->type)) }}</h3>
                                        @if ($productMockups->has($productTemplate->id))
                                            <span class="badge bg-coral-500 text-white text-xs">Custom preview</span>
                                        @endif
                                    </div>

                                    <x-ui.add-to-cart-form :mapping="$mapping" :design="$design" />
                                </div>
                            @endforeach
                        </div>
                    @else
                        {{-- SCENARIO D: design has NO mappings (designer hasn't published it on any product yet) --}}
                        <div class="card-featured">
                            <p class="font-mono">This design is not yet available on any product.</p>
                            <p class="font-mono text-xs text-ink-700 mt-2">
                                The designer hasn't published this artwork on a product yet. Check back later.
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        @else
            {{-- SCENARIO E: design has NO media and NO mappings (truly empty draft) --}}
            <div class="card-featured">
                <h2 class="heading-2 mb-4">{{ $design->title }}</h2>
                <p class="font-mono">This design doesn't have a preview yet.</p>
                @if ($design->designer)
                    <p class="font-mono text-xs text-ink-700 mt-2">
                        Designed by
                        <a href="{{ route('designer.show', $design->designer) }}" class="text-coral-500 hover:underline">
                            {{ $designerName }}
                        </a>
                    </p>
                @endif
            </div>
        @endif

        {{-- REVIEWS -----------------------------------------------------------}}
        <section class="mt-16">
            <div class="flex items-baseline justify-between gap-4 mb-6">
                <h2 class="heading-2">Reviews<span class="text-coral-500">.</span></h2>
                @if ($design->approvedReviews->count() > 0)
                    <p class="font-mono text-sm">
                        <span class="text-coral-500 font-bold">{{ $design->averageRating() }}</span>
                        <span class="text-ink-700">/ 5 · {{ $design->approvedReviews->count() }} {{ \Illuminate\Support\Str::plural('review', $design->approvedReviews->count()) }}</span>
                    </p>
                @endif
            </div>

            {{-- Existing reviews --}}
            @if ($design->approvedReviews->isEmpty())
                <div class="card mb-6">
                    <p class="font-mono">No reviews yet. Be the first to share what you think.</p>
                </div>
            @else
                <div class="space-y-4 mb-6">
                    @foreach ($design->approvedReviews as $review)
                        <div class="card border-3 border-ink-800">
                            <div class="flex items-baseline justify-between gap-3 mb-2">
                                <p class="font-display uppercase text-sm">{{ $review->customer?->name ?? 'Anonymous' }}</p>
                                <p class="font-mono text-xs text-ink-700">{{ $review->created_at->format('Y-m-d') }}</p>
                            </div>
                            <p class="font-mono text-coral-500 mb-2" title="{{ $review->rating }}/5">{{ $review->stars() }}</p>
                            @if ($review->title)
                                <p class="font-display uppercase text-sm mb-2">{{ $review->title }}</p>
                            @endif
                            @if ($review->body)
                                <p class="font-mono text-sm whitespace-pre-line">{{ $review->body }}</p>
                            @endif
                            @can('delete', $review)
                                <form method="POST" action="{{ route('design.reviews.destroy', [$design, $review]) }}" class="mt-2">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="font-mono text-xs text-coral-500 hover:underline">Remove</button>
                                </form>
                            @endcan
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Submit form — only when logged-in customer hasn't reviewed yet --}}
            @auth
                @if (auth()->user()?->isCustomer() && $design->reviewBy(auth()->user()) === null)
                    <div class="card border-5 border-coral-500">
                        <h3 class="heading-3 mb-4">Write a review</h3>
                        <form method="POST" action="{{ route('design.reviews.store', $design) }}" class="space-y-4">
                            @csrf
                            <div>
                                <label class="label" for="rating">Rating</label>
                                <select id="rating" name="rating" required class="input">
                                    @for ($i = 5; $i >= 1; $i--)
                                        <option value="{{ $i }}" @selected(old('rating', 5) == $i)>
                                            {{ $i }} star{{ $i > 1 ? 's' : '' }}
                                        </option>
                                    @endfor
                                </select>
                                @error('rating') <p class="font-mono text-xs text-coral-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="label" for="title">Headline (optional)</label>
                                <input id="title" name="title" type="text" maxlength="120"
                                       value="{{ old('title') }}" class="input"
                                       placeholder="One-line summary">
                                @error('title') <p class="font-mono text-xs text-coral-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="label" for="body">Your review (optional)</label>
                                <textarea id="body" name="body" rows="4" maxlength="2000"
                                          class="input"
                                          placeholder="What did you like about this design?">{{ old('body') }}</textarea>
                                @error('body') <p class="font-mono text-xs text-coral-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <button type="submit" class="btn-coral">Post review</button>
                        </form>
                    </div>
                @elseif (auth()->user()?->isCustomer() && $design->reviewBy(auth()->user()) !== null)
                    <div class="card border-3 border-ink-800">
                        <p class="font-mono text-sm">Thanks — you've already reviewed this design.</p>
                    </div>
                @endif
            @else
                <div class="card border-3 border-ink-800">
                    <p class="font-mono text-sm">
                        <a href="{{ route('login') }}" class="text-coral-500 hover:underline">Sign in</a>
                        as a customer to write a review.
                    </p>
                </div>
            @endauth
        </section>
    </section>
@endsection
