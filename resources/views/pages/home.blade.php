@extends('layouts.app', ['title' => 'POD — Print on demand'])

@section('content')
    {{-- ================================================================
         HERO
         ================================================================ --}}
    <section class="border-b-5 border-ink-800 bg-sand-100">
        <div class="max-w-7xl mx-auto px-6 py-12 md:py-16">
            <div class="grid grid-cols-1 md:grid-cols-[1.4fr_1fr] gap-10 items-center">
                <div>
                    <div class="font-mono uppercase tracking-widest text-xs text-ink-700 mb-6">
                        {{ __('home_hero_tagline') }}
                    </div>

                    <h1 class="font-display text-5xl sm:text-6xl md:text-8xl uppercase leading-[0.95] tracking-tight break-words">
                        {{ __('home_hero_title') }}<br>
                        {{ __('home_hero_title_line2') }}<span class="text-coral-500">.</span>
                    </h1>

                    <p class="mt-6 font-body text-xl max-w-2xl text-ink-800">
                        {{ __('home_hero_body') }}
                    </p>

                    <div class="mt-8">
                        <a href="{{ route('browse.designs') }}" class="btn btn-coral text-base px-8 py-4">
                            {{ __('home_hero_cta') }}
                        </a>
                    </div>
                </div>

                <x-ui.hero-pixel-grid />
            </div>

            @php
                $designCount = \App\Models\Design::where('status', 'published')->whereNull('deleted_at')->count();
                $designerCount = \App\Models\DesignerProfile::has('publishedDesigns')->count();
                $printerCount = \App\Models\PrinterProviderProfile::count();
            @endphp

            @if ($designCount > 0 || $designerCount > 0)
                <div class="mt-10 grid grid-cols-3 gap-6 max-w-3xl border-t-3 border-ink-800 pt-6">
                    <div>
                        <div class="font-display text-3xl text-coral-500">{{ $designCount }}</div>
                        <div class="font-mono text-xs uppercase tracking-wider mt-1">{{ __('home_stats_designs') }}</div>
                    </div>
                    <div>
                        <div class="font-display text-3xl text-coral-500">{{ $designerCount }}</div>
                        <div class="font-mono text-xs uppercase tracking-wider mt-1">{{ __('home_stats_designers') }}</div>
                    </div>
                    <div>
                        <div class="font-display text-3xl text-coral-500">{{ $printerCount }}</div>
                        <div class="font-mono text-xs uppercase tracking-wider mt-1">{{ __('home_stats_print_shops') }}</div>
                    </div>
                </div>
            @endif
        </div>
    </section>

    {{-- ================================================================
         FEATURED DESIGNS
         ================================================================ --}}
    @if ($featuredDesigns->isNotEmpty())
        <section class="max-w-7xl mx-auto px-6 py-20">
            <div class="flex items-end justify-between mb-10 border-b-3 border-ink-800 pb-4">
                <div>
                    <div class="font-mono uppercase tracking-widest text-xs text-coral-500 mb-2">// 01</div>
                    <h2 class="font-display text-4xl md:text-5xl uppercase tracking-wider">{{ __('home_featured_heading') }}</h2>
                </div>
                <a href="{{ route('browse.designs') }}" class="nav-link hidden md:inline-flex items-center gap-2">
                    {{ __('home_see_all') }}
                    <span aria-hidden="true">→</span>
                </a>
            </div>

            <div class="featured-grid">
                @foreach ($featuredDesigns as $design)
                    <x-ui.design-card :design="$design" />
                @endforeach
            </div>

            <div class="mt-8 md:hidden text-center">
                <a href="{{ route('browse.designs') }}" class="nav-link">{{ __('home_see_all_designs') }}</a>
            </div>
        </section>
    @endif

    {{-- ================================================================
         BROWSE BY CATEGORY
         ================================================================ --}}
    @if ($categories->isNotEmpty())
        <section class="bg-sand-200 border-y-5 border-ink-800">
            <div class="max-w-7xl mx-auto px-6 py-20">
                <div class="flex items-end justify-between mb-10 border-b-3 border-ink-800 pb-4">
                    <div>
                        <div class="font-mono uppercase tracking-widest text-xs text-coral-500 mb-2">// 02</div>
                        <h2 class="font-display text-4xl md:text-5xl uppercase tracking-wider">{{ __('home_categories_heading') }}</h2>
                    </div>
                    <a href="{{ route('browse.categories') }}" class="nav-link hidden md:inline-flex items-center gap-2">
                        {{ __('home_all_categories') }}
                        <span aria-hidden="true">→</span>
                    </a>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($categories as $category)
                        <x-ui.category-card :category="$category" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ================================================================
         HOW IT WORKS
         ================================================================ --}}
    <section class="max-w-7xl mx-auto px-6 py-20">
        <div class="mb-12 border-b-3 border-ink-800 pb-4">
            <div class="font-mono uppercase tracking-widest text-xs text-coral-500 mb-2">// 03</div>
            <h2 class="font-display text-4xl md:text-5xl uppercase tracking-wider">{{ __('home_how_heading') }}</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="card-featured">
                <div class="font-mono text-coral-500 text-sm mb-3">{{ __('home_step_01') }}</div>
                <h3 class="heading-3 mb-3">{{ __('home_step_01_heading') }}</h3>
                <p class="font-body text-ink-700">
                    {{ __('home_step_01_body') }}
                </p>
            </div>

            <div class="card-featured">
                <div class="font-mono text-coral-500 text-sm mb-3">{{ __('home_step_02') }}</div>
                <h3 class="heading-3 mb-3">{{ __('home_step_02_heading') }}</h3>
                <p class="font-body text-ink-700">
                    {{ __('home_step_02_body') }}
                </p>
            </div>

            <div class="card-featured">
                <div class="font-mono text-coral-500 text-sm mb-3">{{ __('home_step_03') }}</div>
                <h3 class="heading-3 mb-3">{{ __('home_step_03_heading') }}</h3>
                <p class="font-body text-ink-700">
                    {{ __('home_step_03_body') }}
                </p>
            </div>
        </div>
    </section>

    {{-- ================================================================
         DESIGNERS
         ================================================================ --}}
    @if ($designers->isNotEmpty())
        <section class="bg-sand-200 border-y-5 border-ink-800">
            <div class="max-w-7xl mx-auto px-6 py-20">
                <div class="flex items-end justify-between mb-10 border-b-3 border-ink-800 pb-4">
                    <div>
                        <div class="font-mono uppercase tracking-widest text-xs text-coral-500 mb-2">// 04</div>
                        <h2 class="font-display text-4xl md:text-5xl uppercase tracking-wider">{{ __('home_designers_heading') }}</h2>
                    </div>
                    <a href="{{ route('browse.designers') }}" class="nav-link hidden md:inline-flex items-center gap-2">
                        {{ __('home_all_designers') }}
                        <span aria-hidden="true">→</span>
                    </a>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    @foreach ($designers as $designer)
                        <x-ui.designer-card :designer="$designer" />
                    @endforeach
                </div>

                <div class="mt-10 border-3 border-ink-800 bg-sand-100 p-6 md:p-8 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div>
                        <div class="font-mono uppercase tracking-widest text-xs text-coral-500 mb-2">// JOIN</div>
                        <h3 class="font-display text-3xl md:text-4xl uppercase tracking-wider">{{ __('home_become_designer_heading') }}</h3>
                    </div>
                    <a href="{{ route('register') }}" class="btn btn-coral text-base px-8 py-4 whitespace-nowrap">
                        {{ __('home_become_designer_cta') }} <span aria-hidden="true">→</span>
                    </a>
                </div>
            </div>
        </section>
    @endif

    {{-- ================================================================
         WHY POD — value props
         ================================================================ --}}
    <section class="max-w-7xl mx-auto px-6 py-20">
        <div class="mb-12 border-b-3 border-ink-800 pb-4">
            <div class="font-mono uppercase tracking-widest text-xs text-coral-500 mb-2">// 05</div>
            <h2 class="font-display text-4xl md:text-5xl uppercase tracking-wider">{{ __('home_why_heading') }}</h2>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="card">
                <div class="font-display text-3xl text-coral-500 mb-3">01</div>
                <h3 class="heading-3 mb-2">{{ __('home_why_01_heading') }}</h3>
                <p class="font-mono text-sm text-ink-700">
                    {{ __('home_why_01_body') }}
                </p>
            </div>

            <div class="card">
                <div class="font-display text-3xl text-coral-500 mb-3">02</div>
                <h3 class="heading-3 mb-2">{{ __('home_why_02_heading') }}</h3>
                <p class="font-mono text-sm text-ink-700">
                    {{ __('home_why_02_body') }}
                </p>
            </div>

            <div class="card">
                <div class="font-display text-3xl text-coral-500 mb-3">03</div>
                <h3 class="heading-3 mb-2">{{ __('home_why_03_heading') }}</h3>
                <p class="font-mono text-sm text-ink-700">
                    {{ __('home_why_03_body') }}
                </p>
            </div>

            <div class="card">
                <div class="font-display text-3xl text-coral-500 mb-3">04</div>
                <h3 class="heading-3 mb-2">{{ __('home_why_04_heading') }}</h3>
                <p class="font-mono text-sm text-ink-700">
                    {{ __('home_why_04_body') }}
                </p>
            </div>
        </div>
    </section>

    {{-- ================================================================
         FINAL CTA
         ================================================================ --}}
    <section class="bg-coral-500 border-y-5 border-ink-800">
        <div class="max-w-7xl mx-auto px-6 py-16 md:py-20 text-center">
            <div class="font-mono uppercase tracking-widest text-xs text-ink-900 mb-4">
                {{ __('home_cta_tagline') }}
            </div>
            <h2 class="font-display text-4xl sm:text-5xl md:text-7xl uppercase leading-[0.95] text-white break-words">
                {{ __('home_cta_title') }}<br>{{ __('home_cta_title_line2') }}<span class="text-ink-800">.</span>
            </h2>

            <div class="mt-10 flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('browse.designs') }}" class="btn text-base px-8 py-4">
                    {{ __('home_hero_cta') }}
                </a>
                <a href="{{ route('register') }}" class="btn btn-coral text-base px-8 py-4
                       bg-white text-ink-800 border-ink-800 hover:bg-sand-100 hover:border-sand-100">
                    {{ __('home_become_designer_cta') }}
                </a>
            </div>
        </div>
    </section>
@endsection
