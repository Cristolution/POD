<?php

declare(strict_types=1);

namespace App\Actions\Cart;

use App\Models\CartItem;

class DeleteCartItemAction
{
    public function execute(CartItem $item): void
    {
        $item->delete();
    }
}
