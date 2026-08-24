@extends('layouts.app', ['title' => 'Edit designer profile'])

@section('content')
    <section class="max-w-6xl mx-auto px-6 py-12">
        <x-layout.breadcrumbs :items="[
            'Home' => route('home'),
            'Designer' => route('designer.dashboard'),
            'Edit' => route('designer.edit'),
        ]" />

        <h1 class="heading-1 mb-8">Edit profile<span class="text-coral-500">.</span></h1>

        @if (session('status'))
            <div class="card mb-6 border-coral-500">
                <p class="font-display uppercase text-coral-500">{{ session('status') }}</p>
            </div>
        @endif

        <div class="grid md:grid-cols-[240px_1fr] gap-8">
            <aside class="card bg-sand-200">
                <nav class="space-y-1 font-mono text-sm">
                    @php
                        $links = [
                            ['route' => 'designer.dashboard', 'label' => 'Dashboard'],
                            ['route' => 'designer.edit', 'label' => 'Edit profile'],
                        ];
                    @endphp

                    @foreach ($links as $link)
                        @php $active = request()->routeIs($link['route']); @endphp
                        <a href="{{ route($link['route']) }}"
                           class="block px-3 py-2 border-3 border-ink-800 {{ $active ? 'bg-coral-500 text-white' : 'bg-surface hover:bg-sand-100' }}">
                            {{ $link['label'] }}
                        </a>
                    @endforeach
                </nav>
            </aside>

            <form method="POST" action="{{ route('designer.update') }}" class="card space-y-4">
                @csrf
                @method('PATCH')

                <div>
                    <label class="label" for="bio">Bio</label>
                    <textarea id="bio" name="bio" rows="6" class="input"
                              placeholder="Tell customers about your design style and background.">{{ old('bio', $profile->bio) }}</textarea>
                    @error('bio') <p class="font-mono text-xs text-coral-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="btn-coral">Save profile</button>
                    <a href="{{ route('designer.dashboard') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </section>
@endsection