@extends('layouts.app', ['title' => $printer->company_name])

@section('content')
    @php
        $company = $printer->company_name ?: ($printer->user?->name ?? 'Unknown printer');
        $email = trim(strtolower((string) $printer->user?->email));
        $gravatarUrl = $email
            ? 'https://www.gravatar.com/avatar/'.md5($email).'?d=identicon&s=256'
            : null;
    @endphp

    <section class="max-w-7xl mx-auto px-6 py-12">
        <x-layout.breadcrumbs :items="[
            'Home' => route('home'),
            'Printer' => route('printer.show', $printer),
        ]" />

        <header class="card mb-10 flex flex-col md:flex-row md:items-center md:gap-8 gap-6">
            <div class="w-24 h-24 bg-sand-200 border-3 border-ink-800 overflow-hidden flex-shrink-0">
                @if ($gravatarUrl)
                    <img src="{{ $gravatarUrl }}" alt="" class="w-full h-full object-cover" loading="lazy">
                @endif
            </div>

            <div class="flex-1 min-w-0">
                <h1 class="heading-1 mb-2">{{ $company }}</h1>
                <p class="font-mono text-xs uppercase tracking-wider text-ink-700">
                    {{ $printer->product_templates_count }} product templates
                </p>
            </div>
        </header>

        <h2 class="heading-2 mb-6">Product templates</h2>

        @if ($templates->isEmpty())
            <div class="card-featured text-center">
                <p class="font-mono">No product templates yet.</p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($templates as $template)
                    <article class="card">
                        <div class="font-display uppercase tracking-wider">{{ $template->name }}</div>
                        <div class="font-mono text-xs text-ink-700 mt-1">{{ $template->type }}</div>
                        <div class="font-mono text-sm mt-4">
                            Base cost: ${{ number_format((float) $template->base_cost, 2) }}
                        </div>
                        <div class="font-mono text-xs text-ink-700 mt-1">
                            {{ $template->variants->count() }} variant(s)
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
@endsection