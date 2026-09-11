@extends('layouts.app', ['title' => 'Wishlist'])

@section('content')
    <section class="max-w-6xl mx-auto px-6 py-12">
        <x-layout.breadcrumbs :items="[
            'Home' => route('home'),
            'Account' => route('account.dashboard'),
            'Wishlist' => route('account.wishlist'),
        ]" />

        <h1 class="heading-1 mb-8">Wishlist<span class="text-coral-500">.</span></h1>

        <div class="grid md:grid-cols-[240px_1fr] gap-8">
            <x-layout.account-sidebar />

            <div class="space-y-6">
                @if ($items->isEmpty())
                    <div class="card">
                        <p class="font-mono">No favourites yet.</p>
                        <p class="font-mono text-xs text-ink-700 mt-2">
                            Tap the ♡ icon on any design card to save it for later.
                        </p>
                        <a href="{{ route('browse.designs') }}" class="btn-coral mt-4 inline-block">Browse designs</a>
                    </div>
                @else
                    <p class="font-mono text-sm text-ink-700">
                        {{ $items->count() }} {{ \Illuminate\Support\Str::plural('design', $items->count()) }} saved.
                    </p>
                    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach ($items as $design)
                            <div class="relative">
                                <x-ui.design-card :design="$design" />
                                {{-- Quick "remove" link — same as clicking the heart on the card --}}
                                <form method="POST" action="{{ route('design.wishlist.destroy', $design) }}" class="mt-2">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="font-mono text-xs text-coral-500 hover:underline">
                                        Remove from wishlist
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </section>
@endsection
