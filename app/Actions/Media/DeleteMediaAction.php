<?php

declare(strict_types=1);

namespace App\Actions\Media;

use App\Models\Media;

class DeleteMediaAction
{
    public function execute(Media $media): void
    {
        $media->delete();
    }
}
