@extends('layouts.app', ['title' => 'Categories'])

@section('content')
    <section class="max-w-7xl mx-auto px-6 py-12">
        <x-layout.breadcrumbs :items="[
            'Home' => route('home'),
            'Categories' => route('browse.categories'),
        ]" />

        <h1 class="heading-1 mb-8">Categories</h1>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse ($categories as $category)
                <x-ui.category-card :category="$category" />
            @empty
                <p class="col-span-full text-center py-24 font-mono">No categories yet.</p>
            @endforelse
        </div>
    </section>
@endsection
