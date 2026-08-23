<?php

declare(strict_types=1);

namespace App\Actions\Catalog;

use App\Models\ProductTemplate;

class DeleteProductTemplateAction
{
    public function execute(ProductTemplate $template): void
    {
        abort_if(
            $template->variants()->whereHas('orderItems')->exists(),
            409,
            'Template still has variants referenced by order items.',
        );

        $template->delete();
    }
}
