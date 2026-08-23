<?php

declare(strict_types=1);

namespace App\Actions\Catalog;

use App\Models\Category;

class StoreCategoryAction
{
    /** @param array<string,mixed> $data */
    public function execute(array $data): Category
    {
        return Category::create($data);
    }
}
