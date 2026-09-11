@extends('layouts.app', ['title' => __('printer_dashboard_title')])

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
            __('breadcrumb_printer') => route('printer.dashboard'),
        ]" />

        @if (session('status'))
            <div class="card mb-6 border-coral-500">
                <p class="font-display uppercase text-coral-500">{{ session('status') }}</p>
            </div>
        @endif

        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-8">
            <h1 class="heading-1">{{ __('breadcrumb_printer') }}<span class="text-coral-500">.</span></h1>
            <a href="{{ route('printer.edit') }}" class="btn-coral">{{ __('printer_edit_profile') }}</a>
        </div>

        <div class="grid md:grid-cols-[240px_1fr] gap-8">
            <x-layout.dashboard-sidebar :links="[
                __('printer_sidebar_dashboard') => route('printer.dashboard'),
                __('printer_sidebar_edit_profile') => route('printer.edit'),
            ]" :active="request()->url()" />

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
                            <h2 class="heading-3 truncate">{{ $profile->company_name }}</h2>
                            <p class="font-mono text-xs text-ink-700 mt-1">{{ $name }} · {{ $email }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mt-4">
                        <div class="border-3 border-ink-800 p-4 bg-sand-100">
                            <div class="font-mono text-xs uppercase text-ink-700">{{ __('printer_stat_templates') }}</div>
                            <div class="font-display text-3xl mt-1">{{ $profile->product_templates_count }}</div>
                        </div>
                        <div class="border-3 border-ink-800 p-4 bg-sand-100">
                            <div class="font-mono text-xs uppercase text-ink-700">{{ __('printer_stat_pending_orders') }}</div>
                            <div class="font-display text-3xl mt-1">{{ $profile->pending_order_items_count }}</div>
                        </div>
                    </div>
                </div>

                {{-- Templates --}}
                <div class="card">
                    <h2 class="heading-3 mb-6">{{ __('printer_your_templates') }}</h2>

                    @if ($templates->isEmpty())
                        <p class="font-mono text-sm text-ink-700">{{ __('printer_no_templates') }}</p>
                    @else
                        <div class="table-wrap"><table class="table-pod">
                            <thead>
                                <tr>
                                    <th>{{ __('printer_column_name') }}</th>
                                    <th>{{ __('printer_column_type') }}</th>
                                    <th class="text-right">{{ __('printer_column_base_cost') }}</th>
                                    <th class="text-right">{{ __('printer_column_variants') }}</th>
                                    <th class="text-right">{{ __('printer_column_mappings') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($templates as $template)
                                    <tr>
                                        <td class="font-mono text-sm">{{ $template->name }}</td>
                                        <td>
                                            <span class="badge">{{ $template->type }}</span>
                                        </td>
                                        <td class="font-mono text-sm text-right">
                                            ${{ number_format((float) $template->base_cost, 2) }}
                                        </td>
                                        <td class="font-mono text-sm text-right">{{ $template->variants_count }}</td>
                                        <td class="font-mono text-sm text-right">{{ $template->design_product_mappings_count }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table></div>
                    @endif
                </div>

                {{-- Recent order items --}}
                <div class="card">
                    <h2 class="heading-3 mb-6">{{ __('printer_recent_order_items') }}</h2>

                    @if ($orderItems->isEmpty())
                        <p class="font-mono text-sm text-ink-700">{{ __('printer_no_order_items') }}</p>
                    @else
                        <div class="table-wrap"><table class="table-pod">
                            <thead>
                                <tr>
                                    <th>{{ __('account_order_number') }}</th>
                                    <th>{{ __('printer_column_variant') }}</th>
                                    <th class="text-right">{{ __('printer_column_qty') }}</th>
                                    <th class="text-right">{{ __('printer_column_line_total') }}</th>
                                    <th>{{ __('account_order_status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($orderItems as $item)
                                    <tr>
                                        <td>
                                            @if ($item->order)
                                                <a href="{{ route('orders.confirmation', $item->order) }}"
                                                   class="font-mono text-coral-500 hover:underline">
                                                    {{ substr($item->order->id, 0, 8) }}
                                                </a>
                                            @else
                                                <span class="font-mono text-ink-700">—</span>
                                            @endif
                                        </td>
                                        <td class="font-mono text-sm">
                                            {{ $item->productVariant?->name ?? '—' }}
                                        </td>
                                        <td class="font-mono text-sm text-right">{{ $item->quantity }}</td>
                                        <td class="font-display text-right">
                                            ${{ number_format($item->lineTotal(), 2) }}
                                        </td>
                                        <td>
                                            <span class="badge">{{ $item->status }}</span>
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
