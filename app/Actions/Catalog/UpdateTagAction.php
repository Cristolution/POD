<?php

declare(strict_types=1);

namespace App\Actions\Catalog;

use App\Models\Tag;

class UpdateTagAction
{
    /** @param array<string,mixed> $data */
    public function execute(Tag $tag, array $data): Tag
    {
        $tag->fill($data)->save();

        return $tag->refresh();
    }
}
