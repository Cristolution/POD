<?php

declare(strict_types=1);

namespace App\Actions\Catalog;

use App\Models\PrinterProviderProfile;
use App\Models\ProductTemplate;
use App\Models\User;

class StoreProductTemplateAction
{
    /** @param array<string,mixed> $data */
    public function execute(User $user, array $data): ProductTemplate
    {
        $profile = PrinterProviderProfile::firstOrCreate(
            ['user_id' => $user->id],
            ['company_name' => $user->name.' Print'],
        );

        return $profile->productTemplates()->create($data);
    }
}
