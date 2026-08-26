@extends('layouts.app', ['title' => 'POD — Print on demand'])

@section('content')
    {{-- ================================================================
         HERO
         ================================================================ --}}
    <section class="border-b-5 border-ink-800 bg-sand-100">
        <div class="max-w-7xl mx-auto px-6 py-20 md:py-28">
            <div class="font-mono uppercase tracking-widest text-xs text-ink-700 mb-6">
                Independent designers · Independent fulfillment · One platform
            </div>

            <h1 class="font-display text-6xl md:text-8xl uppercase leading-[0.95] tracking-tight">
                Print<br>
                anything<span class="text-coral-500">.</span>
            </h1>

            <p class="mt-8 font-body text-xl max-w-2xl text-ink-800">
                Original artwork from independent designers, printed and shipped
                by independent print shops. No inventory, no upfront costs — just
                pick a design and we'll handle the rest.
            </p>

            <div class="mt-10 flex flex-col sm:flex-row gap-4">
                <a href="{{ route('browse.designs') }}" class="btn btn-coral text-base px-8 py-4">
                    Browse designs
                </a>
                <a href="{{ route('register') }}" class="btn btn-secondary text-base px-8 py-4">
                    Become a designer
                </a>
            </div>

            @php
                $designCount = \App\Models\Design::where('status', 'published')->whereNull('deleted_at')->count();
                $designerCount = \App\Models\DesignerProfile::has('publishedDesigns')->count();
                $printerCount = \App\Models\PrinterProviderProfile::count();
            @endphp

            @if ($designCount > 0 || $designerCount > 0)
                <div class="mt-12 grid grid-cols-3 gap-6 max-w-3xl border-t-3 border-ink-800 pt-6">
                    <div>
                        <div class="font-display text-3xl text-coral-500">{{ $designCount }}</div>
                        <div class="font-mono text-xs uppercase tracking-wider mt-1">Designs live</div>
                    </div>
                    <div>
                        <div class="font-display text-3xl text-coral-500">{{ $designerCount }}</div>
                        <div class="font-mono text-xs uppercase tracking-wider mt-1">Designers</div>
                    </div>
                    <div>
                        <div class="font-display text-3xl text-coral-500">{{ $printerCount }}</div>
                        <div class="font-mono text-xs uppercase tracking-wider mt-1">Print shops</div>
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
                    <h2 class="font-display text-4xl md:text-5xl uppercase tracking-wider">Featured designs</h2>
                </div>
                <a href="{{ route('browse.designs') }}" class="nav-link hidden md:inline-flex items-center gap-2">
                    See all
                    <span aria-hidden="true">→</span>
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($featuredDesigns as $design)
                    <x-ui.design-card :design="$design" />
                @endforeach
            </div>

            <div class="mt-8 md:hidden text-center">
                <a href="{{ route('browse.designs') }}" class="nav-link">See all designs →</a>
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
                        <h2 class="font-display text-4xl md:text-5xl uppercase tracking-wider">Browse by category</h2>
                    </div>
                    <a href="{{ route('browse.categories') }}" class="nav-link hidden md:inline-flex items-center gap-2">
                        All categories
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
            <h2 class="font-display text-4xl md:text-5xl uppercase tracking-wider">How it works</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="card-featured">
                <div class="font-mono text-coral-500 text-sm mb-3">STEP 01</div>
                <h3 class="heading-3 mb-3">Browse</h3>
                <p class="font-body text-ink-700">
                    Scroll hundreds of original designs across dozens of categories.
                    Filter by style, color, or product.
                </p>
            </div>

            <div class="card-featured">
                <div class="font-mono text-coral-500 text-sm mb-3">STEP 02</div>
                <h3 class="heading-3 mb-3">Pick a print</h3>
                <p class="font-body text-ink-700">
                    Choose a design, pick your product — tee, hoodie, mug, poster —
                    and we'll mock it up for you in seconds.
                </p>
            </div>

            <div class="card-featured">
                <div class="font-mono text-coral-500 text-sm mb-3">STEP 03</div>
                <h3 class="heading-3 mb-3">We ship it</h3>
                <p class="font-body text-ink-700">
                    An independent print shop near you produces the order and ships
                    it directly. No warehouse, no markup.
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
                        <h2 class="font-display text-4xl md:text-5xl uppercase tracking-wider">Meet the designers</h2>
                    </div>
                    <a href="{{ route('browse.designers') }}" class="nav-link hidden md:inline-flex items-center gap-2">
                        All designers
                        <span aria-hidden="true">→</span>
                    </a>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    @foreach ($designers as $designer)
                        <x-ui.designer-card :designer="$designer" />
                    @endforeach
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
            <h2 class="font-display text-4xl md:text-5xl uppercase tracking-wider">Why POD</h2>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="card">
                <div class="font-display text-3xl text-coral-500 mb-3">01</div>
                <h3 class="heading-3 mb-2">Independent designers</h3>
                <p class="font-mono text-sm text-ink-700">
                    Every design is uploaded by a real artist who sets their own price.
                </p>
            </div>

            <div class="card">
                <div class="font-display text-3xl text-coral-500 mb-3">02</div>
                <h3 class="heading-3 mb-2">Independent print shops</h3>
                <p class="font-mono text-sm text-ink-700">
                    Local fulfillment, not a single mega-warehouse halfway across the world.
                </p>
            </div>

            <div class="card">
                <div class="font-display text-3xl text-coral-500 mb-3">03</div>
                <h3 class="heading-3 mb-2">Print on demand</h3>
                <p class="font-mono text-sm text-ink-700">
                    Nothing is made until you order it. Zero waste, zero deadstock.
                </p>
            </div>

            <div class="card">
                <div class="font-display text-3xl text-coral-500 mb-3">04</div>
                <h3 class="heading-3 mb-2">Built for creators</h3>
                <p class="font-mono text-sm text-ink-700">
                    Designers keep control of their work and earn on every sale.
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
                Ready when you are
            </div>
            <h2 class="font-display text-5xl md:text-7xl uppercase leading-[0.95] text-white">
                Print<br>something<span class="text-ink-800">.</span>
            </h2>

            <div class="mt-10 flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('browse.designs') }}" class="btn text-base px-8 py-4">
                    Browse designs
                </a>
                <a href="{{ route('register') }}" class="btn btn-coral text-base px-8 py-4
                       bg-white text-ink-800 border-ink-800 hover:bg-sand-100 hover:border-sand-100">
                    Become a designer
                </a>
            </div>
        </div>
    </section>
@endsection
