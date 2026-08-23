<?php

declare(strict_types=1);

namespace App\Actions\Catalog;

use App\Models\Category;

class DeleteCategoryAction
{
    public function execute(Category $category): void
    {
        abort_if($category->designs()->exists(), 409, 'Category still has active designs.');
        $category->delete();
    }
}
