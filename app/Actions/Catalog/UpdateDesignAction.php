<?php

declare(strict_types=1);

namespace App\Actions\Catalog;

use App\Models\Design;

class UpdateDesignAction
{
    /** @param array<string,mixed> $data */
    public function execute(Design $design, array $data): Design
    {
        $design->fill($data)->save();

        if (array_key_exists('tag_ids', $data)) {
            $design->tags()->sync($data['tag_ids'] ?? []);
        }

        return $design->refresh()->load(['designer', 'category', 'tags']);
    }
}
