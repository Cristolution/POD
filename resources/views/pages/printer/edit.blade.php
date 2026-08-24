@extends('layouts.app', ['title' => 'Edit printer profile'])

@section('content')
    <section class="max-w-6xl mx-auto px-6 py-12">
        <x-layout.breadcrumbs :items="[
            'Home' => route('home'),
            'Printer' => route('printer.dashboard'),
            'Edit' => route('printer.edit'),
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
                            ['route' => 'printer.dashboard', 'label' => 'Dashboard'],
                            ['route' => 'printer.edit', 'label' => 'Edit profile'],
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

            <form method="POST" action="{{ route('printer.update') }}" class="card space-y-4">
                @csrf
                @method('PATCH')

                <div>
                    <label class="label" for="company_name">Company name</label>
                    <input id="company_name" name="company_name" type="text"
                           value="{{ old('company_name', $profile->company_name) }}"
                           required class="input">
                    @error('company_name') <p class="font-mono text-xs text-coral-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="btn-coral">Save profile</button>
                    <a href="{{ route('printer.dashboard') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </section>
@endsection