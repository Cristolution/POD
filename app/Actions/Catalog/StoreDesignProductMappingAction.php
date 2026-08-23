<?php

declare(strict_types=1);

namespace App\Actions\Catalog;

use App\Models\DesignProductMapping;
use App\Models\User;

class StoreDesignProductMappingAction
{
    /** @param array<string,mixed> $data */
    public function execute(User $user, array $data): DesignProductMapping
    {
        $profile = $user->designerProfile
            ?? abort(403, 'Designer profile required to create a design mapping.');

        $designId = $data['design_id'] ?? null;
        $design = $profile->designs()->find($designId)
            ?? abort(403, 'You can only map designs you own.');

        return DesignProductMapping::create([
            'design_id' => $design->id,
            'product_template_id' => $data['product_template_id'],
            'preferred_printer_id' => $data['preferred_printer_id'],
            'final_price' => $data['final_price'],
        ]);
    }
}
