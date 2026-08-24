@extends('layouts.app', ['title' => $design->title])

@section('content')
    @php
        $primary = $design->media->first();
        $imageUrl = $primary
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($primary->file_path)
            : null;
        $designerName = $design->designer?->user?->name ?? 'Unknown';
    @endphp

    <section class="max-w-7xl mx-auto px-6 py-12">
        <x-layout.breadcrumbs :items="[
            'Home' => route('home'),
            'Designs' => route('browse.designs'),
            $design->title => route('design.show', $design),
        ]" />

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
            <div>
                <div class="border-5 border-ink-800 bg-surface aspect-square overflow-hidden">
                    @if ($imageUrl)
                        <img src="{{ $imageUrl }}" alt="{{ $design->title }}" class="w-full h-full object-cover">
                    @else
                        <div class="w-full h-full bg-sand-200 flex items-center justify-center font-display uppercase">
                            No preview
                        </div>
                    @endif
                </div>
            </div>

            <div>
                @if ($design->category)
                    <span class="badge">{{ $design->category->name }}</span>
                @else
                    <span class="badge">Uncategorized</span>
                @endif
                <h1 class="heading-1 mt-4 mb-2">{{ $design->title }}</h1>
                <p class="font-mono text-sm mb-6">
                    by
                    @if ($design->designer)
                        <a href="{{ route('designer.show', $design->designer) }}" class="text-coral-500 hover:underline">
                            {{ $designerName }}
                        </a>
                    @else
                        <span class="text-ink-700">Unknown designer</span>
                    @endif
                </p>

                @forelse ($mappings as $mapping)
                    <x-ui.add-to-cart-form :mapping="$mapping" :design="$design" />
                @empty
                    <div class="card-featured">
                        <p class="font-mono">This design is not yet available on any product.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </section>
@endsection