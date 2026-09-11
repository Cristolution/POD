@extends('layouts.app', ['title' => __('error_403_title')])

@section('content')
    <section class="max-w-2xl mx-auto px-6 py-24 text-center">
        <div class="font-display text-9xl text-coral-500 mb-4">403</div>
        <h1 class="heading-1 mb-4">{{ __('error_403_heading') }}</h1>
        <p class="font-mono mb-8">{{ __('error_403_body') }}</p>
        <a href="{{ route('home') }}" class="btn">{{ __('error_take_me_home') }}</a>
    </section>
@endsection
