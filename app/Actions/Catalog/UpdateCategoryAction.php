<?php

declare(strict_types=1);

namespace App\Actions\Catalog;

use App\Models\Category;

class UpdateCategoryAction
{
    /** @param array<string,mixed> $data */
    public function execute(Category $category, array $data): Category
    {
        $category->fill($data)->save();

        return $category->refresh();
    }
}
