@extends('layouts.app', ['title' => $designer->user?->name ?? 'Designer'])

@section('content')
    @php
        $name = $designer->user?->name ?? 'Unknown designer';
        $email = trim(strtolower((string) $designer->user?->email));
        $gravatarUrl = $email
            ? 'https://www.gravatar.com/avatar/'.md5($email).'?d=identicon&s=256'
            : null;
    @endphp

    <section class="max-w-7xl mx-auto px-6 py-12">
        <x-layout.breadcrumbs :items="[
            'Home' => route('home'),
            'Designers' => route('browse.designers'),
            $name => route('designer.show', $designer),
        ]" />

        <header class="card mb-10 flex flex-col md:flex-row md:items-center md:gap-8 gap-6">
            <div class="w-24 h-24 bg-sand-200 border-3 border-ink-800 overflow-hidden flex-shrink-0">
                @if ($gravatarUrl)
                    <img src="{{ $gravatarUrl }}" alt="" class="w-full h-full object-cover" loading="lazy">
                @endif
            </div>

            <div class="flex-1 min-w-0">
                <h1 class="heading-1 mb-2 flex flex-wrap items-center gap-3">
                    <span>{{ $name }}</span>
                    @if ($designer->is_verified)
                        <span class="badge badge-coral font-display uppercase text-xs tracking-wider">Verified</span>
                    @endif
                </h1>
                <p class="font-mono text-xs uppercase tracking-wider text-ink-700">
                    {{ $designer->published_designs_count }} published
                    / {{ $designer->designs_count }} total designs
                </p>
                @if ($designer->bio)
                    <p class="font-mono text-sm mt-4 whitespace-pre-line">{{ $designer->bio }}</p>
                @endif
            </div>
        </header>

        <h2 class="heading-2 mb-6">Published designs</h2>

        @if ($designs->isEmpty())
            <div class="card-featured text-center">
                <p class="font-mono">No published designs yet.</p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @foreach ($designs as $design)
                    <x-ui.design-card :design="$design" />
                @endforeach
            </div>

            <div class="mt-8">
                <x-ui.pagination :paginator="$designs" />
            </div>
        @endif
    </section>
@endsection