<?php

declare(strict_types=1);

namespace App\Actions\Designer;

use App\Models\DesignerProfile;
use App\Models\User;

class StoreDesignerProfileAction
{
    /** @param array<string,mixed> $data */
    public function execute(User $user, array $data): DesignerProfile
    {
        return $user->designerProfile()->create($data);
    }
}
