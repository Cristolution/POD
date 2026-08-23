<?php

declare(strict_types=1);

namespace App\Actions\Cart;

use App\Models\CartItem;

class UpdateCartItemQuantityAction
{
    public function execute(CartItem $item, int $quantity): CartItem
    {
        abort_unless($quantity >= 1 && $quantity <= 100, 422, 'Quantity must be between 1 and 100.');

        // variant_key is a generated column (depends on product_variant_id), so
        // updating only the quantity preserves the unique-line discriminator
        // automatically.
        $item->quantity = $quantity;
        $item->save();

        return $item->refresh();
    }
}
