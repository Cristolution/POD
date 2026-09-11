@extends('layouts.app', ['title' => __('printer_fulfilment_title')])

@section('content')
    @php
        $user = $profile->user;
        $name = $user?->name ?? __('unknown');
        $email = trim(strtolower((string) $user?->email));
        $gravatarUrl = $email
            ? 'https://www.gravatar.com/avatar/'.md5($email).'?d=identicon&s=128'
            : null;

        $statusBadgeColor = match (true) {
            str_starts_with(request('status', ''), 'received') => 'warning',
            str_starts_with(request('status', ''), 'printing') => 'info',
            str_starts_with(request('status', ''), 'printed') => 'success',
            default => 'gray',
        };
    @endphp

    <section class="max-w-7xl mx-auto px-6 py-12">
        <x-layout.breadcrumbs :items="[
            __('breadcrumb_home') => route('home'),
            __('breadcrumb_printer') => route('printer.dashboard'),
            __('breadcrumb_fulfilment') => route('printer.fulfilment'),
        ]" />

        @if (session('status'))
            <div class="card mb-6 border-coral-500">
                <p class="font-display uppercase text-coral-500">{{ session('status') }}</p>
            </div>
        @endif

        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-8">
            <h1 class="heading-1">{{ __('printer_fulfilment_heading') }}<span class="text-coral-500">.</span></h1>
            <a href="{{ route('printer.dashboard') }}" class="btn btn-secondary">{{ __('printer_back_dashboard') }}</a>
        </div>

        <div class="grid md:grid-cols-[240px_1fr] gap-8">
            <x-layout.dashboard-sidebar :links="[
                __('printer_sidebar_dashboard') => route('printer.dashboard'),
                __('breadcrumb_fulfilment') => route('printer.fulfilment'),
                __('printer_sidebar_edit_profile') => route('printer.edit'),
            ]" :active="request()->url()" />

            <div class="space-y-8">
                @if ($items->isEmpty())
                    <div class="card">
                        <p class="font-mono">{{ __('printer_fulfilment_empty') }}</p>
                    </div>
                @else
                    <div class="card">
                        <p class="font-mono text-sm text-ink-700 mb-4">
                            {{ __('printer_fulfilment_intro') }}
                        </p>
                        <div class="table-wrap"><table class="table-pod">
                            <thead>
                                <tr>
                                    <th>{{ __('account_order_number') }}</th>
                                    <th>{{ __('printer_fulfilment_column_product') }}</th>
                                    <th>{{ __('printer_fulfilment_column_variant') }}</th>
                                    <th class="text-end">{{ __('printer_fulfilment_column_qty') }}</th>
                                    <th class="text-end">{{ __('printer_fulfilment_column_unit') }}</th>
                                    <th>{{ __('account_order_status') }}</th>
                                    <th class="text-end">{{ __('printer_fulfilment_column_action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($items as $item)
                                    @php
                                        $next = $transitions[$item->status] ?? null;
                                        $statusLabel = ucfirst(str_replace('_', ' ', $item->status));
                                        $badgeColor = match ($item->status) {
                                            'pending' => 'gray',
                                            'received' => 'warning',
                                            'printing' => 'info',
                                            'printed' => 'success',
                                            'handed_off' => 'primary',
                                            'cancelled' => 'danger',
                                            default => 'gray',
                                        };
                                    @endphp
                                    <tr>
                                        <td class="font-mono text-sm">
                                            <a href="{{ route('orders.confirmation', $item->order) }}" class="text-coral-500 hover:underline">
                                                {{ Str::limit($item->order->id, 8, '') }}
                                            </a>
                                            <div class="text-xs text-ink-700">{{ $item->order->created_at->format('Y-m-d') }}</div>
                                        </td>
                                        <td class="font-mono text-sm">{{ ucwords(str_replace('-', ' ', $item->designProductMapping?->productTemplate?->type ?? '?')) }}</td>
                                        <td class="font-mono text-sm">{{ $item->productVariant?->label() ?? '—' }}</td>
                                        <td class="font-mono text-sm text-end">{{ $item->quantity }}</td>
                                        <td class="font-mono text-sm text-end">${{ number_format((float) $item->unit_price, 2) }}</td>
                                        <td>
                                            <span class="badge bg-{{ $badgeColor === 'gray' ? 'sand-200' : $badgeColor }}-500 text-{{ $badgeColor === 'gray' ? 'ink-800' : 'white' }}">
                                                {{ $statusLabel }}
                                            </span>
                                        </td>
                                        <td class="font-mono text-sm text-end">
                                            @if ($next)
                                                <form method="POST" action="{{ route('printer.fulfilment.advance', $item) }}" class="inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="next" value="{{ $next }}">
                                                    <button type="submit" class="btn-coral text-xs">
                                                        → {{ ucfirst(str_replace('_', ' ', $next)) }}
                                                    </button>
                                                </form>
                                            @else
                                                <span class="text-ink-700 text-xs">{{ __('printer_fulfilment_done') }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table></div>

                        <div class="mt-6">
                            {{ $items->links() }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </section>
@endsection
