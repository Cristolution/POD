@extends('layouts.app', ['title' => 'Browse designs'])

@section('content')
    <section class="max-w-7xl mx-auto px-6 py-12">
        <x-layout.breadcrumbs :items="[
            'Home' => route('home'),
            'Designs' => route('browse.designs'),
        ]" />

        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-6 mb-8">
            <h1 class="heading-1">Designs</h1>
            <form method="GET" class="flex gap-2">
                <input type="text" name="q" value="{{ request('q') }}"
                       placeholder="Search designs…"
                       class="input flex-1 md:w-80">
                <button class="btn">Search</button>
            </form>
        </div>

        <div class="flex flex-wrap gap-2 mb-8">
            <a href="{{ route('browse.designs') }}"
               class="badge {{ request('category') ? '' : 'badge-coral' }}">All</a>
            @foreach ($categories as $category)
                <a href="{{ route('browse.designs', ['category' => $category->id]) }}"
                   class="badge {{ (int) request('category') === $category->id ? 'badge-coral' : '' }}">
                    {{ $category->name }}
                </a>
            @endforeach
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6">
            @forelse ($designs as $design)
                <x-ui.design-card :design="$design" />
            @empty
                <p class="col-span-full text-center py-24 font-mono">No designs match those filters.</p>
            @endforelse
        </div>

        <x-ui.pagination :paginator="$designs" />
    </section>
@endsection
