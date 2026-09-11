@extends('layouts.app', ['title' => __('auth_forgot_title')])

@section('content')
    <section class="max-w-md mx-auto px-6 py-16">
        <h1 class="heading-1 mb-4 text-center">{{ __('auth_forgot_heading') }}<span class="text-coral-500">.</span></h1>
        <p class="font-mono text-sm text-center mb-8">{{ __('auth_forgot_subtitle') }}</p>

        @if (session('status'))
            <div class="card mb-6 border-coral-500">
                <p class="font-mono text-sm text-coral-500">{{ session('status') }}</p>
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="card-featured space-y-6">
            @csrf

            <div>
                <label class="label" for="email">{{ __('auth_email') }}</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                       class="input" autocomplete="username">
                @error('email') <p class="font-mono text-xs text-coral-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="btn-coral w-full">{{ __('auth_email_reset_link') }}</button>

            <p class="text-center font-mono text-sm">
                {{ __('auth_remembered') }} <a href="{{ route('login') }}" class="text-coral-500 hover:underline">{{ __('auth_back_to_login') }}</a>
            </p>
        </form>
    </section>
@endsection
