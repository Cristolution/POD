@extends('layouts.app', ['title' => 'Forgot password'])

@section('content')
    <section class="max-w-md mx-auto px-6 py-16">
        <h1 class="heading-1 mb-4 text-center">Forgot<span class="text-coral-500">.</span></h1>
        <p class="font-mono text-sm text-center mb-8">Enter your email and we'll send a reset link.</p>

        @if (session('status'))
            <div class="card mb-6 border-coral-500">
                <p class="font-mono text-sm text-coral-500">{{ session('status') }}</p>
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="card-featured space-y-6">
            @csrf

            <div>
                <label class="label" for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                       class="input" autocomplete="username">
                @error('email') <p class="font-mono text-xs text-coral-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="btn-coral w-full">Email reset link</button>

            <p class="text-center font-mono text-sm">
                Remembered? <a href="{{ route('login') }}" class="text-coral-500 hover:underline">Back to login</a>
            </p>
        </form>
    </section>
@endsection