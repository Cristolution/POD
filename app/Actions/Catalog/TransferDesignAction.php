<?php

declare(strict_types=1);

namespace App\Actions\Catalog;

use App\Models\Design;
use App\Models\DesignerProfile;
use Illuminate\Support\Facades\Log;

class TransferDesignAction
{
    public function execute(Design $design, string $newDesignerId): Design
    {
        $target = DesignerProfile::query()->findOrFail($newDesignerId);

        $previousDesignerId = $design->designer_id;

        $design->forceFill([
            'designer_id' => $target->id,
        ])->save();

        Log::channel('audit')->info('design.transferred', [
            'design_id' => $design->id,
            'from_designer_id' => $previousDesignerId,
            'to_designer_id' => $target->id,
        ]);

        return $design->refresh()->load(['designer', 'category', 'tags']);
    }
}
