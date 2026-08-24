<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Models\Media;
use Illuminate\Support\Collection;

class OrphanedMediaAction
{
    /**
     * Find Media rows whose polymorphic owner no longer exists.
     *
     * Cheap in-memory approach: load all media, hydrate the morphTo owner, and
     * keep the rows that resolved to `null`. Acceptable for an admin
     * integrity check that runs rarely.
     *
     * @return Collection<int, Media>
     */
    public function execute(): Collection
    {
        return Media::all()
            ->filter(fn (Media $m): bool => $m->model === null)
            ->values();
    }
}
