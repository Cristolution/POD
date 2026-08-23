<?php

declare(strict_types=1);

namespace App\Actions\Catalog;

use App\Models\Tag;

class StoreTagAction
{
    /** @param array<string,mixed> $data */
    public function execute(array $data): Tag
    {
        return Tag::create($data);
    }
}
