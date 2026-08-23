<?php

declare(strict_types=1);

namespace App\Actions\Designer;

use App\Models\DesignerProfile;

class DeleteDesignerProfileAction
{
    public function execute(DesignerProfile $profile): void
    {
        abort_if($profile->designs()->exists(), 409, 'Designer profile still has active designs.');
        $profile->delete();
    }
}
