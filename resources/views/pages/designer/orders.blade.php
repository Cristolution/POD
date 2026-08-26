@extends('layouts.app', ['title' => 'My orders'])

@section('content')
    <section class="max-w-6xl mx-auto px-6 py-12">
        <x-layout.breadcrumbs :items="[
            'Home' => route('home'),
            'Designer' => route('designer.dashboard'),
            'Orders' => route('designer.orders'),
        ]" />

        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-8">
            <h1 class="heading-1">Orders<span class="text-coral-500">.</span></h1>
            <a href="{{ route('designer.dashboard') }}" class="btn btn-secondary">Back to dashboard</a>
        </div>

        <div class="grid md:grid-cols-[240px_1fr] gap-8">
            @include('partials.designer-sidebar', ['active' => route('designer.orders')])

            <div class="card">
                @if ($items->isEmpty())
                    <p class="font-mono text-sm text-ink-700">
                        No orders yet. When customers buy products featuring your designs, they'll appear here.
                    </p>
                @else
                    <table class="table-pod">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Design</th>
                                <th>Variant</th>
                                <th>Printer</th>
                                <th class="text-right">Qty</th>
                                <th class="text-right">Unit price</th>
                                <th>Status</th>
                                <th>Placed</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($items as $item)
                                <tr>
                                    <td class="font-mono text-sm">{{ Str::limit($item->order_id, 8, '') }}</td>
                                    <td class="font-mono text-sm">
                                        {{ $item->designProductMapping?->design?->title ?? '—' }}
                                    </td>
                                    <td class="font-mono text-sm">{{ $item->productVariant?->name ?? '—' }}</td>
                                    <td class="font-mono text-sm">{{ $item->printerProvider?->user?->name ?? '—' }}</td>
                                    <td class="font-mono text-sm text-right">{{ $item->quantity }}</td>
                                    <td class="font-mono text-sm text-right">
                                        ${{ number_format((float) $item->unit_price, 2) }}
                                    </td>
                                    <td><span class="badge">{{ $item->status }}</span></td>
                                    <td class="font-mono text-sm">{{ $item->created_at?->format('Y-m-d') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="mt-6">
                        <x-ui.pagination :paginator="$items" />
                    </div>
                @endif
            </div>
        </div>
    </section>
@endsection
