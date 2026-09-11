@extends('layouts.app', ['title' => __('auth_register_title')])

@section('content')
    <section class="max-w-md mx-auto px-6 py-16">
        <h1 class="heading-1 mb-8 text-center">{{ __('auth_sign_up_heading') }}<span class="text-coral-500">.</span></h1>

        <form method="POST" action="{{ route('register') }}" class="card-featured space-y-6">
            @csrf

            <div>
                <label class="label" for="name">{{ __('auth_name') }}</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus
                       class="input" autocomplete="name">
                @error('name') <p class="font-mono text-xs text-coral-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="label" for="email">{{ __('auth_email') }}</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required
                       class="input" autocomplete="username">
                @error('email') <p class="font-mono text-xs text-coral-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="label" for="password">{{ __('auth_password') }}</label>
                <input id="password" name="password" type="password" required
                       class="input" autocomplete="new-password">
                @error('password') <p class="font-mono text-xs text-coral-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="label" for="password_confirmation">{{ __('auth_confirm_password') }}</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required
                       class="input" autocomplete="new-password">
            </div>

            <fieldset>
                <legend class="label mb-2">{{ __('auth_signup_as') }}</legend>
                <div class="grid grid-cols-2 gap-3">
                    <label class="card cursor-pointer has-checked:bg-coral-500 has-checked:text-white has-checked:border-coral-500 transition-colors">
                        <input type="radio" name="role" value="customer"
                               {{ old('role', 'customer') === 'customer' ? 'checked' : '' }}
                               class="sr-only">
                        <span class="font-display uppercase tracking-wider text-sm">{{ __('auth_role_customer') }}</span>
                        <span class="font-mono text-xs block mt-1 opacity-80">{{ __('auth_role_customer_hint') }}</span>
                    </label>
                    <label class="card cursor-pointer has-checked:bg-coral-500 has-checked:text-white has-checked:border-coral-500 transition-colors">
                        <input type="radio" name="role" value="designer"
                               {{ old('role', 'customer') === 'designer' ? 'checked' : '' }}
                               class="sr-only">
                        <span class="font-display uppercase tracking-wider text-sm">{{ __('auth_role_designer') }}</span>
                        <span class="font-mono text-xs block mt-1 opacity-80">{{ __('auth_role_designer_hint') }}</span>
                    </label>
                </div>
                @error('role') <p class="font-mono text-xs text-coral-500 mt-1">{{ $message }}</p> @enderror
            </fieldset>

            <button type="submit" class="btn-coral w-full">{{ __('auth_create_account') }}</button>

            <p class="text-center font-mono text-sm">
                {{ __('auth_have_account') }} <a href="{{ route('login') }}" class="text-coral-500 hover:underline">{{ __('nav_login') }}</a>
            </p>
        </form>
    </section>
@endsection
