@extends('layouts.app', ['title' => 'Orders'])

@section('content')
    <section class="max-w-6xl mx-auto px-6 py-12">
        <x-layout.breadcrumbs :items="[
            'Account' => route('account.dashboard'),
            'Orders' => route('account.orders'),
        ]" />

        <h1 class="heading-1 mb-8">Orders<span class="text-coral-500">.</span></h1>

        <div class="grid md:grid-cols-[240px_1fr] gap-8">
            <x-layout.account-sidebar />

            <div>
                @if ($orders->isEmpty())
                    <div class="card-featured text-center">
                        <p class="font-mono mb-6">You haven't placed any orders yet.</p>
                        <a href="{{ route('browse.designs') }}" class="btn">Browse designs</a>
                    </div>
                @else
                    <table class="table-pod">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Payment</th>
                                <th class="text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($orders as $order)
                                <tr>
                                    <td>
                                        <a href="{{ route('orders.confirmation', $order) }}"
                                           class="font-mono text-coral-500 hover:underline">
                                            {{ substr($order->id, 0, 8) }}
                                        </a>
                                    </td>
                                    <td class="font-mono text-sm">
                                        {{ $order->created_at?->format('M j, Y') }}
                                    </td>
                                    <td>
                                        <span class="badge">{{ $order->status }}</span>
                                    </td>
                                    <td>
                                        @if ($order->payments->isNotEmpty())
                                            <span class="badge {{ $order->payments->first()->status === 'confirmed' ? 'badge-coral' : '' }}">
                                                {{ $order->payments->first()->status }}
                                            </span>
                                        @else
                                            <span class="font-mono text-xs text-ink-700">—</span>
                                        @endif
                                    </td>
                                    <td class="font-display text-right">
                                        ${{ number_format((float) $order->total_amount, 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="mt-6">
                        {{ $orders->links() }}
                    </div>
                @endif
            </div>
        </div>
    </section>
@endsection