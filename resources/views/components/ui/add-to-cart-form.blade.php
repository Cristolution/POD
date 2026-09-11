@props(['mapping', 'design'])

@php
    $variants = $mapping->productTemplate?->activeVariants ?? collect();
    $firstVariant = $variants->first();
    $defaultPrice = (float) $mapping->customerPriceFor($firstVariant);
@endphp

<form
    method="POST"
    action="{{ route('cart.items.store') }}"
    x-data="{
        variantId: @js($firstVariant?->id),
        quantity: 1,
        unitPrice: @js($defaultPrice),
        basePrice: @js((float) $mapping->final_price),
        onVariantChange(event) {
            const opt = event.target.selectedOptions[0];
            this.variantId = opt.value || null;
            const delta = parseFloat(opt.dataset.delta || 0);
            this.unitPrice = this.basePrice + delta;
        }
    }"
    class="card-featured"
>
    @csrf
    <input type="hidden" name="design_product_mapping_id" value="{{ $mapping->id }}">
    <input type="hidden" name="product_variant_id" x-model="variantId">

    <h3 class="heading-3 mb-4">
        @php
            $typeLabels = [
                'mug' => 'Mug',
                't-shirt' => 'T-Shirt',
                'poster' => 'Poster',
                'hoodie' => 'Hoodie',
                'tote bag' => 'Tote Bag',
                'cap' => 'Cap',
                'phone case' => 'Phone Case',
                'sticker' => 'Sticker',
            ];
            $type = $mapping->productTemplate?->type;
            $label = $typeLabels[$type] ?? ($type ? ucwords(str_replace('-', ' ', $type)) : null);
        @endphp
        {{ $label ?? 'Product' }}
    </h3>

    @if ($variants->count() > 0)
        <label class="label">Variant</label>
        <select name="variant_choice" @change="onVariantChange($event)" class="input mb-4">
            @foreach ($variants as $variant)
                <option value="{{ $variant->id }}"
                        data-delta="{{ number_format((float) $variant->price_delta, 2, '.', '') }}">
                    {{ $variant->label() }}
                    @if ((float) $variant->price_delta != 0)
                        ({{ $variant->price_delta > 0 ? '+' : '' }}${{ number_format((float) $variant->price_delta, 2) }})
                    @endif
                </option>
            @endforeach
        </select>
    @endif

    <label class="label">Quantity</label>
    <div class="flex items-center gap-2 mb-6">
        <button type="button" @click="quantity = Math.max(1, quantity - 1)" class="btn btn-secondary px-4">&minus;</button>
        <input type="number" name="quantity" x-model.number="quantity" min="1" max="100"
               class="input w-20 text-center">
        <button type="button" @click="quantity = Math.min(100, quantity + 1)" class="btn btn-secondary px-4">+</button>
    </div>

    <div class="border-t-3 border-ink-800 pt-4 mb-4 flex justify-between font-display text-lg">
        <span>Total</span>
        <span x-text="'$' + (unitPrice * quantity).toFixed(2)"></span>
    </div>

    @auth
        <button type="submit" class="btn-coral w-full">Add to cart</button>
    @else
        <a href="{{ route('login', ['redirect' => request()->fullUrl()]) }}" class="btn-coral w-full block text-center">
            Login to add to cart
        </a>
    @endauth
</form>