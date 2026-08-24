@extends('layouts.app', ['title' => 'Service unavailable'])

@section('content')
    <section class="max-w-2xl mx-auto px-6 py-24 text-center">
        <div class="font-display text-9xl text-coral-500 mb-4">503</div>
        <h1 class="heading-1 mb-4">Down for maintenance</h1>
        <p class="font-mono mb-8">We'll be right back. We're working on making things better.</p>
        <a href="{{ route('home') }}" class="btn">Try again</a>
    </section>
@endsection
