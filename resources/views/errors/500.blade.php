@extends('layouts.app', ['title' => 'Server error'])

@section('content')
    <section class="max-w-2xl mx-auto px-6 py-24 text-center">
        <div class="font-display text-9xl text-coral-500 mb-4">500</div>
        <h1 class="heading-1 mb-4">Something went wrong</h1>
        <p class="font-mono mb-8">We hit an unexpected error. Our team has been notified. Please try again in a moment.</p>
        @if (app()->environment('local') && isset($exception))
            <div class="card-featured text-left font-mono text-xs">
                {{ $exception->getMessage() ?? 'No exception details.' }}
            </div>
        @endif
        <a href="{{ route('home') }}" class="btn mt-8">Take me home</a>
    </section>
@endsection
