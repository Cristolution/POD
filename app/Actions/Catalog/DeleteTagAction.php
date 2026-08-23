<?php

declare(strict_types=1);

namespace App\Actions\Catalog;

use App\Models\Tag;

class DeleteTagAction
{
    public function execute(Tag $tag): void
    {
        if ($tag->designs()->exists()) {
            abort(409, 'Tag cannot be deleted while it is attached to one or more designs.');
        }

        $tag->delete();
    }
}
