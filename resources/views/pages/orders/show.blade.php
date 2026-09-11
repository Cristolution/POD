@extends('layouts.app', ['title' => __('orders_show_title_prefix') . substr($order->id, 0, 8)])

@section('content')
    @php
        use Illuminate\Support\Facades\Storage;
    @endphp

    <section class="max-w-4xl mx-auto px-6 py-12">
        <x-layout.breadcrumbs :items="[
            __('breadcrumb_account') => route('account.dashboard'),
            __('breadcrumb_orders') => route('account.orders'),
            substr($order->id, 0, 8) => route('orders.confirmation', $order),
        ]" />

        <header class="mb-8 flex items-center justify-between border-b-5 border-ink-800 pb-6">
            <div>
                <div class="font-mono text-xs uppercase tracking-wider text-ink-700">{{ __('order_label') }}</div>
                <h1 class="heading-1">{{ substr($order->id, 0, 8) }}</h1>
                <div class="font-mono text-sm text-ink-700 mt-1">
                    {{ $order->created_at?->format('M j, Y') }}
                </div>
            </div>
            <span class="font-display uppercase text-sm border-3 border-ink-800 px-3 py-1 bg-surface">
                {{ $order->status }}
            </span>
        </header>

        @if (session('status'))
            <div class="card mb-8 border-coral-500">
                <p class="font-display uppercase text-coral-500">{{ session('status') }}</p>
            </div>
        @endif

        <div class="grid md:grid-cols-2 gap-8 mb-8">
            <div class="card">
                <h2 class="heading-3 mb-3">{{ __('order_shipping_address') }}</h2>
                <p class="font-mono text-sm">
                    {{ $order->shipping_line1 }}<br>
                    {{ $order->shipping_city }}, {{ $order->shipping_country }}<br>
                    @if ($order->shipping_phone)
                        {{ $order->shipping_phone }}
                    @endif
                </p>
            </div>
            <div class="card">
                <h2 class="heading-3 mb-3">{{ __('order_payment_heading') }}</h2>
                @if ($order->payments->isNotEmpty())
                    @php $payment = $order->payments->first(); @endphp
                    <p class="font-mono text-sm">
                        {{ __('order_payment_method') }} <span class="font-display uppercase">{{ str_replace('_', ' ', $payment->method) }}</span><br>
                        {{ __('order_payment_status') }} <span class="font-display uppercase">{{ $payment->status }}</span>
                    </p>
                @else
                    <p class="font-mono text-sm">{{ __('order_no_payment') }}</p>
                @endif
            </div>
        </div>

        <h2 class="heading-2 mb-4">{{ __('order_items_heading') }}</h2>
        <ul class="space-y-3 mb-6">
            @foreach ($order->items as $item)
                @php
                    $mapping = $item->designProductMapping;
                    $design = $mapping?->design;
                    $media = $design?->media->first();
                    $imageUrl = $media ? Storage::disk('public')->url($media->file_path) : null;
                    $variantLabel = $item->productVariant?->label();
                @endphp
                <li class="flex items-center gap-4 border-3 border-ink-800 bg-surface p-4">
                    <div class="w-16 h-16 bg-sand-200 border-3 border-ink-800 overflow-hidden flex-shrink-0">
                        @if ($imageUrl)
                            <img src="{{ $imageUrl }}" alt="" class="w-full h-full object-cover">
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="font-display uppercase truncate">
                            {{ $design?->title ?? __('design') }}
                        </div>
                        <div class="font-mono text-xs text-ink-700">
                            {{ $mapping?->productTemplate?->type ? ucwords(str_replace('-', ' ', $mapping->productTemplate->type)) : __('product') }}
                            @if ($variantLabel) — {{ $variantLabel }} @endif
                        </div>
                        <div class="font-mono text-xs text-ink-700">{{ __('order_quantity', ['count' => $item->quantity]) }}</div>
                    </div>
                    <div class="font-display text-lg">
                        ${{ number_format((float) ($item->unit_price * $item->quantity), 2) }}
                    </div>
                </li>
            @endforeach
        </ul>

        <div class="border-t-5 border-ink-800 pt-4 flex justify-between font-display text-2xl">
            <span>{{ __('total') }}</span>
            <span>${{ number_format((float) $order->total_amount, 2) }}</span>
        </div>

        <div class="mt-8">
            <a href="{{ route('account.orders') }}" class="btn btn-secondary">{{ __('order_view_all') }}</a>
        </div>
    </section>
@endsection
