@extends('layouts.app', ['title' => 'Log in'])

@section('content')
    <section class="max-w-md mx-auto px-6 py-16">
        <h1 class="heading-1 mb-8 text-center">Log in<span class="text-coral-500">.</span></h1>

        <form method="POST" action="{{ route('login') }}" class="card-featured space-y-6">
            @csrf

            <div>
                <label class="label" for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                       class="input" autocomplete="username">
                @error('email') <p class="font-mono text-xs text-coral-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="label" for="password">Password</label>
                <input id="password" name="password" type="password" required
                       class="input" autocomplete="current-password">
                @error('password') <p class="font-mono text-xs text-coral-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <label class="flex items-center gap-2 font-mono text-sm">
                <input type="checkbox" name="remember" value="1">
                Remember me
            </label>

            <button type="submit" class="btn-coral w-full">Log in</button>

            <p class="text-center font-mono text-sm">
                <a href="{{ route('password.request') }}" class="text-coral-500 hover:underline">Forgot your password?</a>
            </p>
            <p class="text-center font-mono text-sm">
                No account? <a href="{{ route('register') }}" class="text-coral-500 hover:underline">Sign up</a>
            </p>
        </form>
    </section>
@endsection