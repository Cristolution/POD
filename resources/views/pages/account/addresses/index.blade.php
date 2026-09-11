@extends('layouts.app', ['title' => __('addresses_title')])

@section('content')
    <section class="max-w-6xl mx-auto px-6 py-12">
        <x-layout.breadcrumbs :items="[
            __('breadcrumb_account') => route('account.dashboard'),
            __('breadcrumb_addresses') => route('account.addresses.index'),
        ]" />

        <div class="flex items-center justify-between mb-8">
            <h1 class="heading-1">{{ __('addresses_heading') }}<span class="text-coral-500">.</span></h1>
            <a href="{{ route('account.addresses.create') }}" class="btn-coral">{{ __('addresses_new') }}</a>
        </div>

        <div class="grid md:grid-cols-[240px_1fr] gap-8">
            <x-layout.account-sidebar />

            <div>
                @if (session('status'))
                    <div class="card mb-6 border-coral-500">
                        <p class="font-display uppercase text-coral-500">{{ session('status') }}</p>
                    </div>
                @endif

                @if ($errors->has('address'))
                    <div class="card mb-6 border-coral-500">
                        <p class="font-mono text-sm text-coral-500">{{ $errors->first('address') }}</p>
                    </div>
                @endif

                @if ($addresses->isEmpty())
                    <div class="card-featured text-center">
                        <p class="font-mono mb-6">{{ __('addresses_empty') }}</p>
                        <a href="{{ route('account.addresses.create') }}" class="btn">{{ __('addresses_add_first') }}</a>
                    </div>
                @else
                    <div class="table-wrap"><table class="table-pod">
                        <thead>
                            <tr>
                                <th>{{ __('addresses_column_address') }}</th>
                                <th>{{ __('auth_phone') }}</th>
                                <th class="text-right">{{ __('addresses_column_actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($addresses as $address)
                                <tr>
                                    <td>
                                        <div class="font-mono text-sm">{{ $address->line1 }}</div>
                                        <div class="font-mono text-xs text-ink-700">
                                            {{ $address->city }}, {{ $address->country }}
                                        </div>
                                    </td>
                                    <td class="font-mono text-sm">
                                        {{ $address->phone ?? '—' }}
                                    </td>
                                    <td class="text-right">
                                        <div class="inline-flex gap-2">
                                            <a href="{{ route('account.addresses.edit', $address) }}"
                                               class="btn btn-secondary text-xs">{{ __('addresses_edit') }}</a>
                                            <form method="POST"
                                                  action="{{ route('account.addresses.destroy', $address) }}"
                                                  onsubmit="return confirm('{{ __('addresses_confirm_delete') }}')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-secondary text-xs">{{ __('addresses_delete') }}</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table></div>
                @endif
            </div>
        </div>
    </section>
@endsection
