@extends('layouts.app', ['title' => __('designers_title')])

@section('content')
    <section class="max-w-7xl mx-auto px-6 py-12">
        <x-layout.breadcrumbs :items="[
            __('breadcrumb_home') => route('home'),
            __('breadcrumb_designers') => route('browse.designers'),
        ]" />

        <h1 class="heading-1 mb-8">{{ __('designers_heading') }}</h1>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse ($designers as $designer)
                <x-ui.designer-card :designer="$designer" />
            @empty
                <p class="col-span-full text-center py-24 font-mono">{{ __('designers_empty') }}</p>
            @endforelse
        </div>

        <x-ui.pagination :paginator="$designers" />
    </section>
@endsection
