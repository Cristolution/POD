@extends('layouts.app', ['title' => __('auth_reset_title')])

@section('content')
    <section class="max-w-md mx-auto px-6 py-16">
        <h1 class="heading-1 mb-8 text-center">{{ __('auth_reset_heading') }}<span class="text-coral-500">.</span></h1>

        <form method="POST" action="{{ route('password.store') }}" class="card-featured space-y-6">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div>
                <label class="label" for="email">{{ __('auth_email') }}</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                       class="input" autocomplete="username">
                @error('email') <p class="font-mono text-xs text-coral-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="label" for="password">{{ __('auth_new_password') }}</label>
                <input id="password" name="password" type="password" required
                       class="input" autocomplete="new-password">
                @error('password') <p class="font-mono text-xs text-coral-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="label" for="password_confirmation">{{ __('auth_confirm') }}</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required
                       class="input" autocomplete="new-password">
            </div>

            <button type="submit" class="btn-coral w-full">{{ __('auth_reset_button') }}</button>
        </form>
    </section>
@endsection
