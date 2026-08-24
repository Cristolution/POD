@extends('layouts.app', ['title' => 'Sign up'])

@section('content')
    <section class="max-w-md mx-auto px-6 py-16">
        <h1 class="heading-1 mb-8 text-center">Sign up<span class="text-coral-500">.</span></h1>

        <form method="POST" action="{{ route('register') }}" class="card-featured space-y-6">
            @csrf

            <div>
                <label class="label" for="name">Name</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus
                       class="input" autocomplete="name">
                @error('name') <p class="font-mono text-xs text-coral-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="label" for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required
                       class="input" autocomplete="username">
                @error('email') <p class="font-mono text-xs text-coral-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="label" for="password">Password</label>
                <input id="password" name="password" type="password" required
                       class="input" autocomplete="new-password">
                @error('password') <p class="font-mono text-xs text-coral-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="label" for="password_confirmation">Confirm password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required
                       class="input" autocomplete="new-password">
            </div>

            <button type="submit" class="btn-coral w-full">Create account</button>

            <p class="text-center font-mono text-sm">
                Already have an account? <a href="{{ route('login') }}" class="text-coral-500 hover:underline">Log in</a>
            </p>
        </form>
    </section>
@endsection