@extends('layouts.app', ['title' => 'Designers'])

@section('content')
    <section class="max-w-7xl mx-auto px-6 py-12">
        <x-layout.breadcrumbs :items="[
            'Home' => route('home'),
            'Designers' => route('browse.designers'),
        ]" />

        <h1 class="heading-1 mb-8">Designers</h1>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse ($designers as $designer)
                <x-ui.designer-card :designer="$designer" />
            @empty
                <p class="col-span-full text-center py-24 font-mono">No designers yet.</p>
            @endforelse
        </div>

        <x-ui.pagination :paginator="$designers" />
    </section>
@endsection
