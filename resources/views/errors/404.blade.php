@extends('layouts.app', ['title' => 'Not found'])

@section('content')
    <section class="max-w-2xl mx-auto px-6 py-24 text-center">
        <div class="font-display text-9xl text-coral-500 mb-4">404</div>
        <h1 class="heading-1 mb-4">Page not found</h1>
        <p class="font-mono mb-8">The thing you're looking for doesn't exist or has moved.</p>
        <a href="{{ route('home') }}" class="btn">Take me home</a>
    </section>
@endsection
