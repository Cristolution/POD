@extends('layouts.app', ['title' => __('auth_login_title')])

@section('content')
    <section class="max-w-md mx-auto px-6 py-16">
        <h1 class="heading-1 mb-8 text-center">{{ __('auth_login_heading') }}<span class="text-coral-500">.</span></h1>

        <form method="POST" action="{{ route('login') }}" class="card-featured space-y-6">
            @csrf

            <div>
                <label class="label" for="email">{{ __('auth_email') }}</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                       class="input" autocomplete="username">
                @error('email') <p class="font-mono text-xs text-coral-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="label" for="password">{{ __('auth_password') }}</label>
                <input id="password" name="password" type="password" required
                       class="input" autocomplete="current-password">
                @error('password') <p class="font-mono text-xs text-coral-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <label class="flex items-center gap-2 font-mono text-sm">
                <input type="checkbox" name="remember" value="1">
                {{ __('auth_remember_me') }}
            </label>

            <button type="submit" class="btn-coral w-full">{{ __('auth_login_button') }}</button>

            <p class="text-center font-mono text-sm">
                <a href="{{ route('password.request') }}" class="text-coral-500 hover:underline">{{ __('auth_forgot_password') }}</a>
            </p>
            <p class="text-center font-mono text-sm">
                {{ __('auth_no_account') }} <a href="{{ route('register') }}" class="text-coral-500 hover:underline">{{ __('auth_sign_up') }}</a>
            </p>
        </form>
    </section>
@endsection
