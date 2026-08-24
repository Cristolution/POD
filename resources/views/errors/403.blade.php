@extends('layouts.app', ['title' => 'Forbidden'])

@section('content')
    <section class="max-w-2xl mx-auto px-6 py-24 text-center">
        <div class="font-display text-9xl text-coral-500 mb-4">403</div>
        <h1 class="heading-1 mb-4">Access denied</h1>
        <p class="font-mono mb-8">You don't have permission to view this resource.</p>
        <a href="{{ route('home') }}" class="btn">Take me home</a>
    </section>
@endsection
