@extends('layouts.app', ['title' => __('designer_dashboard_title')])

@section('content')
    @php
        $user = $profile->user;
        $name = $user?->name ?? __('unknown');
        $email = trim(strtolower((string) $user?->email));
        $gravatarUrl = $email
            ? 'https://www.gravatar.com/avatar/'.md5($email).'?d=identicon&s=128'
            : null;
    @endphp

    <section class="max-w-6xl mx-auto px-6 py-12">
        <x-layout.breadcrumbs :items="[
            __('breadcrumb_home') => route('home'),
            __('breadcrumb_designer') => route('designer.dashboard'),
        ]" />

        @if (session('status'))
            <div class="card mb-6 border-coral-500">
                <p class="font-display uppercase text-coral-500">{{ session('status') }}</p>
            </div>
        @endif

        @unless ($profile->is_verified)
            <div class="card mb-6 border-coral-500 bg-sand-50">
                <p class="font-display uppercase text-coral-500 text-sm">{{ __('pending_review_heading') }}</p>
                <p class="font-mono text-xs text-ink-700 mt-2">
                    {{ __('pending_review_body') }}
                </p>
            </div>
        @endunless

        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-8">
            <h1 class="heading-1">{{ __('breadcrumb_designer') }}<span class="text-coral-500">.</span></h1>
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('designer.designs.create') }}" class="btn-coral">{{ __('designer_create_design') }}</a>
                <a href="{{ route('designer.edit') }}" class="btn btn-secondary">{{ __('designer_edit_profile') }}</a>
            </div>
        </div>

        <div class="grid md:grid-cols-[240px_1fr] gap-8">
            @include('partials.designer-sidebar', ['active' => request()->url()])

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
                            <div class="font-mono text-xs uppercase text-ink-700">{{ __('designer_stat_published') }}</div>
                            <div class="font-display text-3xl mt-1">{{ $profile->published_designs_count }}</div>
                        </div>
                        <div class="border-3 border-ink-800 p-4 bg-sand-100">
                            <div class="font-mono text-xs uppercase text-ink-700">{{ __('designer_stat_all') }}</div>
                            <div class="font-display text-3xl mt-1">{{ $profile->designs_count }}</div>
                        </div>
                    </div>
                </div>

                {{-- Designs table --}}
                <div class="card">
                    <h2 class="heading-3 mb-6">{{ __('designer_your_designs') }}</h2>

                    @if ($designs->isEmpty())
                        <p class="font-mono text-sm text-ink-700">{{ __('designer_no_designs_yet') }}</p>
                    @else
                        <div class="table-wrap"><table class="table-pod">
                            <thead>
                                <tr>
                                    <th>{{ __('designer_column_title') }}</th>
                                    <th>{{ __('account_order_status') }}</th>
                                    <th>{{ __('designer_column_category') }}</th>
                                    <th class="text-end">{{ __('designer_column_mappings') }}</th>
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
                                        <td class="font-mono text-sm text-end">
                                            {{ $design->mappings_count }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table></div>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection
