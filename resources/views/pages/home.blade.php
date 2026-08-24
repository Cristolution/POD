@extends('layouts.app', ['title' => 'POD — Print on demand'])

@section('content')
    <section class="max-w-7xl mx-auto px-6 py-16">
        <h1 class="heading-1 mb-6">Print anything<span class="text-coral-500">.</span></h1>
        <p class="font-body text-lg max-w-2xl">
            Independent designers. Independent fulfillment. One platform.
        </p>
        <div class="mt-8 flex gap-4">
            <a href="{{ route('browse.designs') }}" class="btn">Browse designs</a>
            <a href="{{ route('register') }}" class="btn btn-secondary">Become a designer</a>
        </div>
    </section>
@endsection