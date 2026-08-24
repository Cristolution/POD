@extends('layouts.app', ['title' => 'Addresses'])

@section('content')
    <section class="max-w-6xl mx-auto px-6 py-12">
        <x-layout.breadcrumbs :items="[
            'Account' => route('account.dashboard'),
            'Addresses' => route('account.addresses.index'),
        ]" />

        <div class="flex items-center justify-between mb-8">
            <h1 class="heading-1">Addresses<span class="text-coral-500">.</span></h1>
            <a href="{{ route('account.addresses.create') }}" class="btn-coral">+ New address</a>
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
                        <p class="font-mono mb-6">You have no saved addresses yet.</p>
                        <a href="{{ route('account.addresses.create') }}" class="btn">Add your first address</a>
                    </div>
                @else
                    <table class="table-pod">
                        <thead>
                            <tr>
                                <th>Address</th>
                                <th>Phone</th>
                                <th class="text-right">Actions</th>
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
                                               class="btn btn-secondary text-xs">Edit</a>
                                            <form method="POST"
                                                  action="{{ route('account.addresses.destroy', $address) }}"
                                                  onsubmit="return confirm('Delete this address?')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-secondary text-xs">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </section>
@endsection