<?php

declare(strict_types=1);

namespace App\Actions\Designer;

use App\Models\DesignerProfile;

class UpdateDesignerProfileAction
{
    /** @param array<string,mixed> $data */
    public function execute(DesignerProfile $profile, array $data): DesignerProfile
    {
        $profile->fill($data)->save();

        return $profile->refresh();
    }
}
