@extends('layouts.app', ['title' => __('cart_title')])

@section('content')
    @php
        use Illuminate\Support\Facades\Storage;
    @endphp

    <section class="max-w-5xl mx-auto px-6 py-12">
        <x-layout.breadcrumbs :items="[
            __('breadcrumb_home') => route('home'),
            __('breadcrumb_cart') => route('cart.show'),
        ]" />

        <h1 class="heading-1 mb-8">{{ __('cart_heading') }}<span class="text-coral-500">.</span></h1>

        @if ($items->isEmpty())
            <div class="card-featured text-center">
                <p class="font-mono mb-6">{{ __('cart_empty') }}</p>
                <a href="{{ route('browse.designs') }}" class="btn">{{ __('cart_browse_designs') }}</a>
            </div>
        @else
            <div class="space-y-4">
                @foreach ($items as $item)
                    @php
                        $mapping = $item->designProductMapping;
                        $design = $mapping?->design;
                        $media = $design?->media->first();
                        $imageUrl = $media ? Storage::disk('public')->url($media->file_path) : null;
                        $variantLabel = $item->productVariant?->label();
                    @endphp
                    <div class="card flex flex-col sm:flex-row sm:items-center gap-4 sm:gap-6">
                        <div class="w-24 h-24 bg-sand-200 border-3 border-ink-800 overflow-hidden flex-shrink-0">
                            @if ($imageUrl)
                                <img src="{{ $imageUrl }}" alt="" class="w-full h-full object-cover">
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            @if ($design)
                                <a href="{{ route('design.show', $design) }}" class="heading-3 hover:text-coral-500 block truncate">
                                    {{ $design->title }}
                                </a>
                            @endif
                            <div class="font-mono text-sm text-ink-700 mt-1">
                                {{ $mapping?->productTemplate?->type ? ucwords(str_replace('-', ' ', $mapping->productTemplate->type)) : __('product') }}
                                @if ($variantLabel)
                                    — {{ $variantLabel }}
                                @endif
                            </div>
                        </div>
                        <form method="POST" action="{{ route('cart.items.update', $item) }}"
                              class="flex items-center gap-2 shrink-0">
                            @csrf
                            @method('PATCH')
                            <input type="number" name="quantity" value="{{ $item->quantity }}"
                                   min="1" max="100" class="input w-20 text-center">
                            <button class="btn btn-secondary text-xs">{{ __('cart_update') }}</button>
                        </form>
                        <div class="font-display text-lg sm:w-24 sm:text-right shrink-0">
                            ${{ number_format((float) $item->lineTotal(), 2) }}
                        </div>
                        <form method="POST" action="{{ route('cart.items.destroy', $item) }}" class="shrink-0">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-secondary text-xs w-full sm:w-auto">{{ __('cart_remove') }}</button>
                        </form>
                    </div>
                @endforeach
            </div>

            <div class="mt-8 border-t-5 border-ink-800 pt-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <form method="POST" action="{{ route('cart.clear') }}">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-secondary w-full sm:w-auto">{{ __('cart_clear') }}</button>
                </form>
                <div class="sm:text-right">
                    <div class="font-mono text-xs uppercase tracking-wider">{{ __('total') }}</div>
                    <div class="font-display text-3xl sm:text-4xl break-all">${{ number_format((float) $grandTotal, 2) }}</div>
                </div>
            </div>

            <div class="mt-8 text-center sm:text-right">
                <a href="{{ route('checkout.show') }}" class="btn btn-coral text-lg w-full sm:w-auto">{{ __('cart_proceed_checkout') }}</a>
            </div>
        @endif
    </section>
@endsection
