<?php

declare(strict_types=1);

namespace App\Actions\Catalog;

use App\Models\DesignProductMapping;

class UpdateDesignProductMappingAction
{
    /**
     * Only columns that actually exist on `design_product_mappings`.
     * Note: `is_active` is part of the planned public surface but is not yet
     * a physical column; consumers should request it via query params.
     *
     * @param  array<string,mixed>  $data
     */
    public function execute(DesignProductMapping $mapping, array $data): DesignProductMapping
    {
        $allowed = [
            'final_price',
            'preferred_printer_id',
        ];

        $mapping->fill(collect($data)->only($allowed)->all())->save();

        return $mapping->refresh();
    }
}
