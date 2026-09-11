@extends('layouts.app', ['title' => __('printer_edit_title')])

@section('content')
    <section class="max-w-6xl mx-auto px-6 py-12">
        <x-layout.breadcrumbs :items="[
            __('breadcrumb_home') => route('home'),
            __('breadcrumb_printer') => route('printer.dashboard'),
            __('breadcrumb_edit') => route('printer.edit'),
        ]" />

        <h1 class="heading-1 mb-8">{{ __('printer_edit_heading') }}<span class="text-coral-500">.</span></h1>

        @if (session('status'))
            <div class="card mb-6 border-coral-500">
                <p class="font-display uppercase text-coral-500">{{ session('status') }}</p>
            </div>
        @endif

        <div class="grid md:grid-cols-[240px_1fr] gap-8">
            <x-layout.dashboard-sidebar :links="[
                __('printer_sidebar_dashboard') => route('printer.dashboard'),
                __('printer_sidebar_edit_profile') => route('printer.edit'),
            ]" :active="request()->url()" />

            <form method="POST" action="{{ route('printer.update') }}" class="card space-y-4">
                @csrf
                @method('PATCH')

                <div>
                    <label class="label" for="company_name">{{ __('printer_company_name') }}</label>
                    <input id="company_name" name="company_name" type="text"
                           value="{{ old('company_name', $profile->company_name) }}"
                           required class="input">
                    @error('company_name') <p class="font-mono text-xs text-coral-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="btn-coral">{{ __('designer_save_profile') }}</button>
                    <a href="{{ route('printer.dashboard') }}" class="btn btn-secondary">{{ __('cancel') }}</a>
                </div>
            </form>
        </div>
    </section>
@endsection
