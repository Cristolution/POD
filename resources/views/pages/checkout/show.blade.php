@extends('layouts.app', ['title' => 'Checkout'])

@section('content')
    @php
        use Illuminate\Support\Facades\Storage;
    @endphp

    <section class="max-w-5xl mx-auto px-6 py-12">
        <x-layout.breadcrumbs :items="[
            'Home' => route('home'),
            'Cart' => route('cart.show'),
            'Checkout' => route('checkout.show'),
        ]" />

        <h1 class="heading-1 mb-8">Checkout<span class="text-coral-500">.</span></h1>

        <form method="POST" action="{{ route('checkout.place') }}" class="grid md:grid-cols-[1fr_360px] gap-8">
            @csrf

            <div class="space-y-6">
                <div>
                    <label class="label" for="address_id">Shipping address</label>
                    @if ($addresses->isEmpty())
                        <p class="font-mono text-sm">
                            No saved addresses.
                            <a href="{{ route('account.dashboard') }}" class="text-coral-500 hover:underline">Add one in your account →</a>
                        </p>
                    @else
                        <select id="address_id" name="address_id" class="input" required>
                            <option value="">Select an address…</option>
                            @foreach ($addresses as $address)
                                <option value="{{ $address->id }}" @selected(old('address_id') == $address->id)>
                                    {{ $address->oneLine() }}
                                </option>
                            @endforeach
                        </select>
                    @endif
                    @error('address_id') <p class="font-mono text-xs text-coral-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <span class="label">Payment method</span>
                    <div class="space-y-2">
                        @foreach ($paymentMethods as $method)
                            <label class="flex items-center gap-3 p-3 border-3 border-ink-800 bg-surface cursor-pointer hover:bg-sand-200">
                                <input
                                    type="radio"
                                    name="payment_method"
                                    value="{{ $method }}"
                                    @checked(old('payment_method') === $method)
                                    required
                                >
                                <span class="font-display uppercase">{{ str_replace('_', ' ', $method) }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('payment_method') <p class="font-mono text-xs text-coral-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="card bg-sand-200">
                    <p class="font-mono text-xs uppercase tracking-wider mb-2">Delivery</p>
                    <p class="font-mono text-sm">
                        A delivery company will be assigned during fulfilment. Options on file:
                    </p>
                    <ul class="font-mono text-sm mt-2 list-disc pl-6">
                        @forelse ($deliveryCompanies as $dc)
                            <li>{{ $dc->name }}</li>
                        @empty
                            <li>No delivery companies configured yet.</li>
                        @endforelse
                    </ul>
                </div>
            </div>

            <aside>
                <h2 class="heading-2 mb-4">Summary</h2>
                <ul class="space-y-3 mb-6">
                    @foreach ($items as $item)
                        @php
                            $mapping = $item->designProductMapping;
                            $design = $mapping?->design;
                            $media = $design?->media->first();
                            $imageUrl = $media ? Storage::disk('public')->url($media->file_path) : null;
                            $variantLabel = $item->productVariant?->label();
                        @endphp
                        <li class="flex items-center gap-3 border-3 border-ink-800 bg-surface p-3">
                            <div class="w-12 h-12 bg-sand-200 border-3 border-ink-800 overflow-hidden flex-shrink-0">
                                @if ($imageUrl)
                                    <img src="{{ $imageUrl }}" alt="" class="w-full h-full object-cover">
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="font-display uppercase text-sm truncate">
                                    {{ $design?->title ?? 'Design' }}
                                </div>
                                <div class="font-mono text-xs">
                                    {{ $mapping?->productTemplate?->type ? ucwords(str_replace('-', ' ', $mapping->productTemplate->type)) : 'Product' }}
                                    @if ($variantLabel) — {{ $variantLabel }} @endif
                                    × {{ $item->quantity }}
                                </div>
                            </div>
                            <div class="font-display text-sm">
                                ${{ number_format((float) $item->lineTotal(), 2) }}
                            </div>
                        </li>
                    @endforeach
                </ul>

                <div class="border-t-5 border-ink-800 pt-4 flex justify-between font-display text-2xl">
                    <span>Total</span>
                    <span>${{ number_format((float) $grandTotal, 2) }}</span>
                </div>

                <button type="submit" class="btn-coral w-full mt-6 text-lg">Place order</button>
            </aside>
        </form>
    </section>
@endsection
