@extends('layouts.app', ['title' => __('designer_orders_title')])

@section('content')
    <section class="max-w-6xl mx-auto px-6 py-12">
        <x-layout.breadcrumbs :items="[
            __('breadcrumb_home') => route('home'),
            __('breadcrumb_designer') => route('designer.dashboard'),
            __('breadcrumb_orders') => route('designer.orders'),
        ]" />

        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-8">
            <h1 class="heading-1">{{ __('orders_heading') }}<span class="text-coral-500">.</span></h1>
            <a href="{{ route('designer.dashboard') }}" class="btn btn-secondary">{{ __('designer_back_dashboard') }}</a>
        </div>

        <div class="grid md:grid-cols-[240px_1fr] gap-8">
            @include('partials.designer-sidebar', ['active' => route('designer.orders')])

            <div class="card">
                @if ($items->isEmpty())
                    <p class="font-mono text-sm text-ink-700">
                        {{ __('designer_orders_empty') }}
                    </p>
                @else
                    <div class="table-wrap"><table class="table-pod">
                        <thead>
                            <tr>
                                <th>{{ __('account_order_number') }}</th>
                                <th>{{ __('designer_orders_column_design') }}</th>
                                <th>{{ __('designer_orders_column_variant') }}</th>
                                <th>{{ __('designer_orders_column_printer') }}</th>
                                <th class="text-end">{{ __('designer_orders_column_qty') }}</th>
                                <th class="text-end">{{ __('designer_orders_column_unit_price') }}</th>
                                <th>{{ __('account_order_status') }}</th>
                                <th>{{ __('designer_orders_column_placed') }}</th>
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
                                    <td class="font-mono text-sm text-end">{{ $item->quantity }}</td>
                                    <td class="font-mono text-sm text-end">
                                        ${{ number_format((float) $item->unit_price, 2) }}
                                    </td>
                                    <td><span class="badge">{{ $item->status }}</span></td>
                                    <td class="font-mono text-sm">{{ $item->created_at?->format('Y-m-d') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table></div>

                    <div class="mt-6">
                        <x-ui.pagination :paginator="$items" />
                    </div>
                @endif
            </div>
        </div>
    </section>
@endsection
