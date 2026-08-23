<?php

declare(strict_types=1);

namespace App\Actions\Catalog;

use App\Models\Design;

class DeleteDesignAction
{
    public function execute(Design $design): void
    {
        // SoftDeletes trait — sets deleted_at instead of removing the row.
        $design->delete();
    }
}
