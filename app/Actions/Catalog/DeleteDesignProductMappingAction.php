<?php

declare(strict_types=1);

namespace App\Actions\Catalog;

use App\Models\DesignProductMapping;

class DeleteDesignProductMappingAction
{
    public function execute(DesignProductMapping $mapping): void
    {
        abort_if(
            $mapping->orderItems()->exists(),
            409,
            'Design mapping cannot be deleted while order items reference it.',
        );

        $mapping->delete();
    }
}
