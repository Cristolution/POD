<?php

declare(strict_types=1);

namespace App\Actions\Cart;

use App\Models\CartItem;
use App\Models\DesignProductMapping;
use App\Models\ProductVariant;
use App\Models\User;

class UpsertCartItemAction
{
    public function execute(User $user, string $mappingId, ?string $variantId, int $quantity): CartItem
    {
        abort_unless($quantity >= 1 && $quantity <= 100, 422, 'Quantity must be between 1 and 100.');

        $mapping = DesignProductMapping::findOrFail($mappingId);
        $variant = $variantId !== null ? ProductVariant::findOrFail($variantId) : null;

        // Look up an existing line for (user, mapping, variant) — including the
        // null-variant case. We avoid updateOrCreate's automatic INSERT because
        // variant_key is a generated column we cannot write through Eloquent.
        $cartItem = CartItem::query()
            ->where('user_id', $user->id)
            ->where('design_product_mapping_id', $mapping->id)
            ->when($variant === null, fn ($q) => $q->whereNull('product_variant_id'))
            ->when($variant !== null, fn ($q) => $q->where('product_variant_id', $variant->id))
            ->first();

        if ($cartItem === null) {
            $cartItem = new CartItem;
            $cartItem->user_id = $user->id;
            $cartItem->design_product_mapping_id = $mapping->id;
            $cartItem->product_variant_id = $variant?->id;
            $cartItem->quantity = $quantity;
            $cartItem->save();
        } else {
            // Existing line — merge the new quantity into the existing one
            // rather than overwriting, matching the mergeQuantity contract.
            $cartItem->mergeQuantity($quantity);
        }

        return $cartItem->refresh();
    }
}
