@extends('layouts.app', ['title' => __('designer_edit_profile_title')])

@section('content')
    <section class="max-w-6xl mx-auto px-6 py-12">
        <x-layout.breadcrumbs :items="[
            __('breadcrumb_home') => route('home'),
            __('breadcrumb_designer') => route('designer.dashboard'),
            __('breadcrumb_edit') => route('designer.edit'),
        ]" />

        <h1 class="heading-1 mb-8">{{ __('designer_edit_heading') }}<span class="text-coral-500">.</span></h1>

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

        <div class="grid md:grid-cols-[240px_1fr] gap-8">
            @include('partials.designer-sidebar', ['active' => request()->url()])

            <form method="POST" action="{{ route('designer.update') }}" class="card space-y-4">
                @csrf
                @method('PATCH')

                <div>
                    <label class="label" for="bio">{{ __('designer_bio_label') }}</label>
                    <textarea id="bio" name="bio" rows="6" class="input"
                              placeholder="{{ __('designer_bio_placeholder') }}">{{ old('bio', $profile->bio) }}</textarea>
                    @error('bio') <p class="font-mono text-xs text-coral-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="btn-coral">{{ __('designer_save_profile') }}</button>
                    <a href="{{ route('designer.dashboard') }}" class="btn btn-secondary">{{ __('cancel') }}</a>
                </div>
            </form>
        </div>
    </section>
@endsection
