@props([
    'categories',
    'designers',
    'activeCategory',
    'activeDesigners',
    'priceMin',
    'priceMax',
])

@php
    $selectedDesignerIds = collect($activeDesigners ?? [])->pluck('id')->all();
    $hasActiveFilters = ($activeCategory ?? false)
        || $selectedDesignerIds !== []
        || ($priceMin !== null && $priceMin > 0)
        || ($priceMax !== null && $priceMax > 0);
    $activeFilterCount = ($activeCategory ? 1 : 0)
        + count($selectedDesignerIds)
        + ($priceMin ? 1 : 0)
        + ($priceMax ? 1 : 0);
@endphp

{{-- ============================================================
     Mobile filter trigger — inline at top of the column, NOT its own column
     ============================================================ --}}
<div x-data="{ open: false }" class="lg:hidden mb-4">
    <button type="button" @click="open = true"
            class="btn w-full flex items-center justify-between gap-2 py-2">
        <span class="flex items-center gap-2">
            <span class="font-mono text-xs uppercase tracking-widest">{{ __('facets_refine') }}</span>
            @if ($activeFilterCount > 0)
                <span class="badge badge-coral text-[10px] py-0">
                    {{ $activeFilterCount }} {{ __('facets_active') }}
                </span>
            @endif
        </span>
        <span class="font-mono">↓</span>
    </button>

    <div x-show="open" x-transition.opacity @click="open = false"
         class="fixed inset-0 bg-ink-900/60 z-40" x-cloak></div>

    <div x-show="open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-4"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-4"
         class="fixed inset-x-0 top-0 z-50 bg-sand-100 overflow-y-auto border-b-5 border-ink-800 max-h-[90vh]"
         x-cloak>
        <div class="sticky top-0 z-10 bg-ink-800 text-sand-100 px-5 py-3 flex items-center justify-between border-b-3 border-coral-500">
            <span class="font-display uppercase tracking-wider text-sm">{{ __('facets_refine_results') }}</span>
            <button type="button" @click="open = false" class="font-mono text-xs uppercase tracking-widest hover:text-coral-500">{{ __('facets_close') }}</button>
        </div>

        <div class="p-5">
            <form method="GET" action="{{ route('browse.designs') }}" class="space-y-5">
                @if (request('q'))
                    <input type="hidden" name="q" value="{{ request('q') }}">
                @endif
                @if (request('sort') && request('sort') !== 'newest')
                    <input type="hidden" name="sort" value="{{ request('sort') }}">
                @endif

                {{-- Categories --}}
                <div>
                    <span class="block font-mono text-[10px] uppercase tracking-widest text-ink-700 mb-2">{{ __('facets_category') }}</span>
                    <select name="category" class="input font-mono text-sm py-2">
                        <option value="">{{ __('facets_all_categories') }}</option>
                        @foreach ($categories as $root)
                            <option value="{{ $root->id }}" {{ ($activeCategory && $activeCategory->id === $root->id) ? 'selected' : '' }}>
                                {{ $root->name }} ({{ $root->designs_count }})
                            </option>
                            @foreach ($root->children as $child)
                                <option value="{{ $child->id }}" {{ ($activeCategory && $activeCategory->id === $child->id) ? 'selected' : '' }}>
                                    &nbsp;&nbsp;{{ $child->name }}
                                </option>
                            @endforeach
                        @endforeach
                    </select>
                </div>

                {{-- Price --}}
                <div>
                    <span class="block font-mono text-[10px] uppercase tracking-widest text-ink-700 mb-2">{{ __('facets_price_usd') }}</span>
                    <div class="flex items-center gap-2">
                        <div class="flex-1 relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 font-mono">$</span>
                            <input type="number" name="price_min" value="{{ $priceMin }}" min="0" step="1" placeholder="{{ __('facets_min') }}"
                                   class="input w-full ps-7 font-mono text-sm py-2">
                        </div>
                        <span class="font-display">→</span>
                        <div class="flex-1 relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 font-mono">$</span>
                            <input type="number" name="price_max" value="{{ $priceMax }}" min="0" step="1" placeholder="{{ __('facets_max') }}"
                                   class="input w-full ps-7 font-mono text-sm py-2">
                        </div>
                    </div>
                    <div class="grid grid-cols-4 gap-1.5 mt-2">
                        @foreach ([['min'=>0,'max'=>10],['min'=>10,'max'=>25],['min'=>25,'max'=>50],['min'=>50,'max'=>null]] as $chip)
                            @php
                                $chipMin = (float) $chip['min'];
                                $chipMax = $chip['max'] !== null ? (float) $chip['max'] : 0;
                                $isActive = (float)($priceMin ?? 0) === $chipMin && (float)($priceMax ?? 0) === $chipMax;
                            @endphp
                            <a href="{{ route('browse.designs', array_merge(request()->except(['page','price_min','price_max']), array_filter(['price_min'=>$chip['min']?:null,'price_max'=>$chip['max']?:null]))) }}"
                               class="font-mono text-[10px] uppercase tracking-widest text-center px-1 py-1.5 border-3 transition-colors {{ $isActive ? 'bg-coral-500 text-white border-coral-500' : 'bg-sand-100 text-ink-800 border-ink-800 hover:bg-coral-500 hover:text-white hover:border-coral-500' }}">
                                {{ $chip['max'] === null ? '$'.$chip['min'].'+' : '$'.$chip['min'].'–'.$chip['max'] }}
                            </a>
                        @endforeach
                    </div>
                </div>

                {{-- Designer --}}
                <div>
                    <span class="block font-mono text-[10px] uppercase tracking-widest text-ink-700 mb-2">{{ __('facets_designer') }}</span>
                    <select name="designer[]" multiple size="6" class="input font-mono text-sm py-2 min-h-[8rem]">
                        @foreach ($designers as $designer)
                            <option value="{{ $designer->id }}" {{ in_array($designer->id, $selectedDesignerIds, true) ? 'selected' : '' }}>
                                {{ $designer->user?->name ?? __('unknown') }} · {{ $designer->published_designs_count }}
                            </option>
                        @endforeach
                    </select>
                    <span class="block mt-1 font-mono text-[10px] uppercase tracking-widest text-ink-700">{{ __('facets_multi_select_hint') }}</span>
                </div>

                <div class="flex gap-2 pt-2">
                    <button type="submit" class="btn flex-1">{{ __('facets_apply') }}</button>
                    @if ($hasActiveFilters)
                        <a href="{{ route('browse.designs', request()->except(['category','designer','price_min','price_max','page'])) }}"
                           class="btn bg-surface hover:bg-ink-800 hover:text-sand-100"
                           @click="open = false">{{ __('facets_clear') }}</a>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ============================================================
     Desktop sidebar (lg+) — minimal, no boxy sections
     ============================================================ --}}
<aside class="hidden lg:block w-1/5 shrink-0 pe-6">
    <form method="GET" action="{{ route('browse.designs') }}" id="facets-form" class="space-y-5 lg:sticky lg:top-20">
        @if (request('q'))
            <input type="hidden" name="q" value="{{ request('q') }}">
        @endif
        @if (request('sort') && request('sort') !== 'newest')
            <input type="hidden" name="sort" value="{{ request('sort') }}">
        @endif
        @if (request('tag'))
            <input type="hidden" name="tag" value="{{ request('tag') }}">
        @endif

        {{-- Categories as compact pill list --}}
        <div>
            <div class="flex items-baseline justify-between mb-2 pb-1 border-b-3 border-ink-800">
                <span class="font-display uppercase tracking-wider text-xs">{{ __('facets_categories_heading') }}</span>
                @if ($activeCategory)
                    <a href="{{ route('browse.designs', request()->except(['category','page'])) }}" class="font-mono text-[10px] uppercase tracking-widest text-coral-600 hover:text-coral-700">{{ __('facets_reset') }}</a>
                @endif
            </div>
            <ul class="space-y-1 font-mono text-xs">
                <li>
                    <a href="{{ route('browse.designs', request()->except(['category','page'])) }}"
                       class="block py-1 hover:text-coral-500 {{ ! $activeCategory ? 'text-coral-600 font-bold' : '' }}">
                        {{ __('facets_all') }}
                    </a>
                </li>
                @foreach ($categories as $root)
                    @php $isActiveRoot = $activeCategory && $activeCategory->id === $root->id; @endphp
                    <li>
                        <a href="{{ route('browse.designs', array_merge(request()->except(['page','category']), ['category' => $root->id])) }}"
                           class="flex items-center justify-between py-1 hover:text-coral-500 {{ $isActiveRoot ? 'text-coral-600 font-bold' : '' }}">
                            <span>{{ $root->name }}</span>
                            <span class="text-[10px] opacity-60">{{ $root->designs_count }}</span>
                        </a>
                        @if ($root->children->isNotEmpty() && $isActiveRoot)
                            <ul class="ps-3 mt-1 space-y-1 border-s-3 border-ink-800 ms-1">
                                @foreach ($root->children as $child)
                                    <li>
                                        <a href="{{ route('browse.designs', array_merge(request()->except(['page','category']), ['category' => $child->id])) }}"
                                           class="block py-1 text-[11px] hover:text-coral-500 {{ ($activeCategory && $activeCategory->id === $child->id) ? 'text-coral-600 font-bold' : '' }}">
                                            {{ $child->name }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>

        {{-- Price — minimal --}}
        <div>
            <div class="flex items-baseline justify-between mb-2 pb-1 border-b-3 border-ink-800">
                <span class="font-display uppercase tracking-wider text-xs">{{ __('facets_price_heading') }}</span>
            </div>
            <div class="flex items-center gap-1.5">
                <div class="relative flex-1">
                    <span class="absolute left-2 top-1/2 -translate-y-1/2 font-mono text-xs text-ink-700">$</span>
                    <input type="number" name="price_min" value="{{ $priceMin }}" min="0" step="1" placeholder="{{ __('facets_min') }}"
                           class="input w-full ps-6 font-mono text-xs py-1.5">
                </div>
                <span class="font-mono text-xs">–</span>
                <div class="relative flex-1">
                    <span class="absolute left-2 top-1/2 -translate-y-1/2 font-mono text-xs text-ink-700">$</span>
                    <input type="number" name="price_max" value="{{ $priceMax }}" min="0" step="1" placeholder="{{ __('facets_max') }}"
                           class="input w-full ps-6 font-mono text-xs py-1.5">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-1 mt-2 font-mono text-[10px] uppercase tracking-widest">
                @foreach ([['min'=>0,'max'=>10,'label'=>'< $10'],['min'=>10,'max'=>25,'label'=>'$10–25'],['min'=>25,'max'=>50,'label'=>'$25–50'],['min'=>50,'max'=>null,'label'=>'$50+']] as $chip)
                    @php
                        $chipMin = (float) $chip['min'];
                        $chipMax = $chip['max'] !== null ? (float) $chip['max'] : 0;
                        $isActive = (float)($priceMin ?? 0) === $chipMin && (float)($priceMax ?? 0) === $chipMax;
                    @endphp
                    <a href="{{ route('browse.designs', array_merge(request()->except(['page','price_min','price_max']), array_filter(['price_min'=>$chip['min']?:null,'price_max'=>$chip['max']?:null]))) }}"
                       class="text-center px-1 py-1.5 border-3 transition-colors {{ $isActive ? 'bg-coral-500 text-white border-coral-500' : 'border-ink-800 hover:bg-coral-500 hover:text-white hover:border-coral-500' }}">
                        {{ $chip['label'] }}
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Designer — minimal dropdown --}}
        <div>
            <div class="flex items-baseline justify-between mb-2 pb-1 border-b-3 border-ink-800">
                <span class="font-display uppercase tracking-wider text-xs">{{ __('facets_designer_heading') }}</span>
                @if (count($selectedDesignerIds) > 0)
                    <a href="{{ route('browse.designs', request()->except(['designer','page'])) }}" class="font-mono text-[10px] uppercase tracking-widest text-coral-600 hover:text-coral-700">{{ __('facets_reset') }}</a>
                @endif
            </div>
            <select name="designer[]" multiple size="5" class="input font-mono text-xs py-1.5 min-h-[7rem]">
                @foreach ($designers as $designer)
                    <option value="{{ $designer->id }}" {{ in_array($designer->id, $selectedDesignerIds, true) ? 'selected' : '' }}>
                        {{ $designer->user?->name ?? __('unknown') }} · {{ $designer->published_designs_count }}
                    </option>
                @endforeach
            </select>
            <button type="submit" class="btn w-full mt-2 py-1.5 text-xs">{{ __('facets_apply_designers') }}</button>
        </div>

        {{-- Clear all --}}
        @if ($hasActiveFilters)
            <a href="{{ route('browse.designs', request()->except(['category','designer','price_min','price_max','page'])) }}"
               class="block text-center font-mono text-[10px] uppercase tracking-widest underline text-ink-700 hover:text-coral-500">
                {{ __('facets_clear_all') }}
            </a>
        @endif
    </form>
</aside>

@once
    @push('head')
        <style>[x-cloak] { display: none !important; }</style>
    @endpush
@endonce
