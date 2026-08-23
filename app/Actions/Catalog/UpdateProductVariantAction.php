<?php

declare(strict_types=1);

namespace App\Actions\Catalog;

use App\Models\ProductVariant;

class UpdateProductVariantAction
{
    /** @param array<string,mixed> $data */
    public function execute(ProductVariant $variant, array $data): ProductVariant
    {
        $variant->fill($data)->save();

        return $variant->refresh();
    }
}
