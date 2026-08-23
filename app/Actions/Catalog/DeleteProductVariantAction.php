<?php

declare(strict_types=1);

namespace App\Actions\Catalog;

use App\Models\ProductVariant;

class DeleteProductVariantAction
{
    public function execute(ProductVariant $variant): void
    {
        abort_if($variant->orderItems()->exists(), 409, 'Variant still has active order items.');
        $variant->delete();
    }
}
