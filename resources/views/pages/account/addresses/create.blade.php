@extends('layouts.app', ['title' => 'New address'])

@section('content')
    <section class="max-w-6xl mx-auto px-6 py-12">
        <x-layout.breadcrumbs :items="[
            'Account' => route('account.dashboard'),
            'Addresses' => route('account.addresses.index'),
            'New' => route('account.addresses.create'),
        ]" />

        <h1 class="heading-1 mb-8">New address<span class="text-coral-500">.</span></h1>

        <div class="grid md:grid-cols-[240px_1fr] gap-8">
            <x-layout.account-sidebar />

            <form method="POST" action="{{ route('account.addresses.store') }}" class="card space-y-4">
                @csrf

                <div>
                    <label class="label" for="line1">Address line 1</label>
                    <input id="line1" name="line1" type="text" value="{{ old('line1') }}"
                           required class="input" autocomplete="address-line1">
                    @error('line1') <p class="font-mono text-xs text-coral-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label" for="city">City</label>
                    <input id="city" name="city" type="text" value="{{ old('city') }}"
                           required class="input" autocomplete="address-level2">
                    @error('city') <p class="font-mono text-xs text-coral-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label" for="country">Country</label>
                    <input id="country" name="country" type="text" value="{{ old('country') }}"
                           required class="input" autocomplete="country-name">
                    @error('country') <p class="font-mono text-xs text-coral-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label" for="phone">Phone</label>
                    <input id="phone" name="phone" type="text" value="{{ old('phone') }}"
                           class="input" autocomplete="tel">
                    @error('phone') <p class="font-mono text-xs text-coral-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="btn-coral">Save address</button>
                    <a href="{{ route('account.addresses.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </section>
@endsection