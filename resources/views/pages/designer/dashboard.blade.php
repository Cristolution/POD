@extends('layouts.app', ['title' => 'Designer dashboard'])

@section('content')
    @php
        $user = $profile->user;
        $name = $user?->name ?? 'Unknown';
        $email = trim(strtolower((string) $user?->email));
        $gravatarUrl = $email
            ? 'https://www.gravatar.com/avatar/'.md5($email).'?d=identicon&s=128'
            : null;
    @endphp

    <section class="max-w-6xl mx-auto px-6 py-12">
        <x-layout.breadcrumbs :items="[
            'Home' => route('home'),
            'Designer' => route('designer.dashboard'),
        ]" />

        @if (session('status'))
            <div class="card mb-6 border-coral-500">
                <p class="font-display uppercase text-coral-500">{{ session('status') }}</p>
            </div>
        @endif

        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-8">
            <h1 class="heading-1">Designer<span class="text-coral-500">.</span></h1>
            <a href="{{ route('designer.edit') }}" class="btn-coral">Edit profile</a>
        </div>

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

            <div class="space-y-8">
                {{-- Summary card --}}
                <div class="card">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-16 h-16 bg-sand-200 border-3 border-ink-800 overflow-hidden flex-shrink-0">
                            @if ($gravatarUrl)
                                <img src="{{ $gravatarUrl }}" alt="" class="w-full h-full object-cover" loading="lazy">
                            @endif
                        </div>
                        <div class="min-w-0">
                            <h2 class="heading-3 truncate">{{ $name }}</h2>
                            <p class="font-mono text-xs text-ink-700 mt-1">{{ $email }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mt-4">
                        <div class="border-3 border-ink-800 p-4 bg-sand-100">
                            <div class="font-mono text-xs uppercase text-ink-700">Published</div>
                            <div class="font-display text-3xl mt-1">{{ $profile->published_designs_count }}</div>
                        </div>
                        <div class="border-3 border-ink-800 p-4 bg-sand-100">
                            <div class="font-mono text-xs uppercase text-ink-700">All designs</div>
                            <div class="font-display text-3xl mt-1">{{ $profile->designs_count }}</div>
                        </div>
                    </div>
                </div>

                {{-- Designs table --}}
                <div class="card">
                    <h2 class="heading-3 mb-6">Your designs</h2>

                    @if ($designs->isEmpty())
                        <p class="font-mono text-sm text-ink-700">No designs yet.</p>
                    @else
                        <table class="table-pod">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Status</th>
                                    <th>Category</th>
                                    <th class="text-right">Mappings</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($designs as $design)
                                    <tr>
                                        <td>
                                            <a href="{{ route('design.show', $design) }}"
                                               class="font-mono text-coral-500 hover:underline">
                                                {{ $design->title }}
                                            </a>
                                        </td>
                                        <td>
                                            <span class="badge {{ $design->status === 'published' ? 'badge-coral' : '' }}">
                                                {{ $design->status }}
                                            </span>
                                        </td>
                                        <td class="font-mono text-sm">
                                            {{ $design->category?->name ?? '—' }}
                                        </td>
                                        <td class="font-mono text-sm text-right">
                                            {{ $design->mappings_count }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection