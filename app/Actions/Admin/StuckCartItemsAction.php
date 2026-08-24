<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Models\CartItem;
use Illuminate\Support\Collection;

class StuckCartItemsAction
{
    /**
     * Cart items that reference a soft-deleted product variant or a
     * soft-deleted design-product mapping — these would otherwise be silently
     * broken at checkout.
     *
     * @return Collection<int, CartItem>
     */
    public function execute(): Collection
    {
        return CartItem::with([
            'productVariant' => fn ($q) => $q->withTrashed(),
            'designProductMapping' => fn ($q) => $q->withTrashed(),
        ])
            ->get()
            ->filter(fn (CartItem $item): bool => ($item->product_variant_id !== null && $item->productVariant?->trashed())
                || $item->designProductMapping?->trashed() === true)
            ->values();
    }
}
