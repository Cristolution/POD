<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CartItem;
use App\Models\DesignProductMapping;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;

class CartPricingService
{
    /**
     * @return array{unit_price: float, line_total: float, variant_key: string}
     */
    public function priceLine(DesignProductMapping $mapping, ?ProductVariant $variant, int $quantity): array
    {
        $unitPrice = $mapping->customerPriceFor($variant);
        $variantKey = $this->resolveVariantKey($mapping, $variant);

        return [
            'unit_price' => $unitPrice,
            'line_total' => $unitPrice * $quantity,
            'variant_key' => $variantKey,
        ];
    }

    /**
     * Sum the line totals across the provided items.
     *
     * @param  Collection<int, CartItem>  $items
     */
    public function grandTotal(Collection $items): float
    {
        return (float) $items->sum(fn (CartItem $item): float => $item->lineTotal());
    }

    /**
     * Stable per-line discriminator: mapping id + variant id (or 'null').
     * Matches the `(user_id, design_product_mapping_id, variant_key)` unique index
     * so that the same variant on the same mapping collapses into one cart row.
     */
    private function resolveVariantKey(DesignProductMapping $mapping, ?ProductVariant $variant): string
    {
        return $mapping->id.':'.($variant?->id ?? 'null');
    }
}
