@extends('layouts.app', ['title' => __('browse_designs_title')])

@section('content')
    <section class="max-w-7xl mx-auto px-4 md:px-6 py-6 md:py-8">

        {{-- Page heading + search (Amazon-style) --}}
        <header class="mb-6">
            <h1 class="font-display uppercase tracking-wider text-4xl md:text-6xl leading-none mb-4">{{ __('browse_designs_heading') }}</h1>
            <form method="GET" action="{{ route('browse.designs') }}" class="flex gap-2">
                @foreach (request()->except(['q','page']) as $key => $value)
                    @if (is_array($value))
                        @foreach ($value as $v)
                            <input type="hidden" name="{{ $key }}[]" value="{{ $v }}">
                        @endforeach
                    @else
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach
                <input type="search" name="q" value="{{ request('q') }}"
                       placeholder="{{ __('browse_search_placeholder') }}"
                       class="input flex-1 py-2.5 md:py-3 text-sm md:text-base">
                <button class="btn px-4 md:px-8 shrink-0">{{ __('browse_search_button') }}</button>
            </form>
        </header>

        {{-- Layout: stacked on mobile, sidebar (20%) + main (80%) on desktop --}}
        <div class="lg:flex lg:items-start">

            {{-- Sidebar / mobile drawer --}}
            <x-ui.facets
                :categories="$categories"
                :designers="$designers"
                :active-category="$activeCategory"
                :active-designers="$activeDesigners"
                :price-min="$priceMin"
                :price-max="$priceMax"
            />

            {{-- Main column — 100% mobile, 80% desktop --}}
            <div class="w-full lg:w-4/5 min-w-0">

                {{-- Top action bar: sort + count (always visible) --}}
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4 pb-3 border-b-3 border-ink-800">
                    <p class="font-mono text-xs uppercase tracking-widest text-ink-700">
                        <span class="font-display text-base text-ink-800 not-italic">{{ $designs->count() }}</span>
                        {{ __('browse_of_total', ['total' => $designs->total()]) }}
                    </p>

                    <form method="GET" class="flex items-center gap-2">
                        @foreach (request()->except(['sort','page']) as $key => $value)
                            @if (is_array($value))
                                @foreach ($value as $v)
                                    <input type="hidden" name="{{ $key }}[]" value="{{ $v }}">
                                @endforeach
                            @else
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endif
                        @endforeach
                        <label for="sort" class="font-mono text-[10px] uppercase tracking-widest text-ink-700">{{ __('browse_sort_label') }}</label>
                        <select name="sort" id="sort"
                                class="bg-surface border-3 border-ink-800 px-2 py-1 font-mono text-xs uppercase tracking-wider focus:outline-none focus:border-coral-500 cursor-pointer"
                                onchange="this.form.submit()">
                            @foreach ($sortOptions as $value => $label)
                                <option value="{{ $value }}" {{ $sort === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>

                {{-- Refine-by chips row --}}
                @php
                    $chips = [];
                    if ($activeCategory) {
                        $chips[] = ['label' => $activeCategory->name, 'param' => 'category'];
                    }
                    foreach ($activeDesigners as $d) {
                        $chips[] = ['label' => $d->user?->name ?? __('unknown'), 'param' => 'designer[]', 'value' => $d->id];
                    }
                    if ($priceMin) {
                        $chips[] = ['label' => '$'.rtrim(rtrim(number_format((float) $priceMin, 2), '0'), '.').'+', 'param' => 'price_min'];
                    }
                    if ($priceMax) {
                        $chips[] = ['label' => 'to $'.rtrim(rtrim(number_format((float) $priceMax, 2), '0'), '.'), 'param' => 'price_max'];
                    }
                @endphp
                @if (count($chips) > 0)
                    <div class="flex flex-wrap items-center gap-2 mb-4">
                        <span class="font-mono text-[10px] uppercase tracking-widest text-ink-700 shrink-0">{{ __('browse_refine') }}</span>
                        @foreach ($chips as $chip)
                            @php $params = request()->except(['page', $chip['param']]); @endphp
                            <a href="{{ route('browse.designs', $params) }}"
                               class="group inline-flex items-center gap-1.5 bg-coral-500 text-white border-3 border-ink-800 ps-2.5 pe-1 py-0.5 font-mono text-[10px] uppercase tracking-widest hover:bg-coral-600 transition-colors">
                                <span>{{ $chip['label'] }}</span>
                                <span class="bg-ink-800 text-sand-100 w-4 h-4 flex items-center justify-center text-xs leading-none group-hover:bg-coral-700">×</span>
                            </a>
                        @endforeach
                        <a href="{{ route('browse.designs', request()->except(['category','designer','price_min','price_max','page'])) }}"
                           class="ms-auto font-mono text-[10px] uppercase tracking-widest underline text-ink-700 hover:text-coral-500 shrink-0">
                            {{ __('browse_clear_all') }}
                        </a>
                    </div>
                @endif

                {{-- Grid: 1 col mobile, 2 cols md, 3 cols lg+ --}}
                <div class="browse-grid">
                    @forelse ($designs as $design)
                        <x-ui.design-card :design="$design" />
                    @empty
                        <div class="col-span-full bg-surface border-5 border-ink-800 p-8 md:p-12 text-center">
                            <p class="font-display uppercase tracking-wider text-2xl md:text-3xl mb-3">{{ __('browse_no_matches_heading') }}</p>
                            <p class="font-mono text-sm text-ink-700 max-w-md mx-auto">
                                {{ __('browse_no_matches_body') }}
                                <a href="{{ route('browse.designs') }}" class="underline hover:text-coral-500">{{ __('browse_no_matches_link') }}</a>.
                            </p>
                        </div>
                    @endforelse
                </div>

                <div class="mt-8 md:mt-12">
                    <x-ui.pagination :paginator="$designs" />
                </div>
            </div>
        </div>
    </section>
@endsection
