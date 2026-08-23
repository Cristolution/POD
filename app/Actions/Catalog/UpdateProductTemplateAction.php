<?php

declare(strict_types=1);

namespace App\Actions\Catalog;

use App\Models\ProductTemplate;

class UpdateProductTemplateAction
{
    /** @param array<string,mixed> $data */
    public function execute(ProductTemplate $template, array $data): ProductTemplate
    {
        $template->fill($data)->save();

        return $template->refresh();
    }
}
