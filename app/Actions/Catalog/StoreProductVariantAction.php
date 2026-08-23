<?php

declare(strict_types=1);

namespace App\Actions\Catalog;

use App\Models\ProductTemplate;
use App\Models\ProductVariant;

class StoreProductVariantAction
{
    /** @param array<string,mixed> $data */
    public function execute(ProductTemplate $template, array $data): ProductVariant
    {
        return $template->variants()->create($data);
    }
}
