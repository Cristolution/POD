@extends('layouts.app', ['title' => __('error_500_title')])

@section('content')
    <section class="max-w-2xl mx-auto px-6 py-24 text-center">
        <div class="font-display text-9xl text-coral-500 mb-4">500</div>
        <h1 class="heading-1 mb-4">{{ __('error_500_heading') }}</h1>
        <p class="font-mono mb-8">{{ __('error_500_body') }}</p>
        @if (app()->environment('local') && isset($exception))
            <div class="card-featured text-left font-mono text-xs">
                {{ $exception->getMessage() ?? __('error_no_exception_details') }}
            </div>
        @endif
        <a href="{{ route('home') }}" class="btn mt-8">{{ __('error_take_me_home') }}</a>
    </section>
@endsection
