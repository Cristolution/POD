<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\Models\User;

class DeleteMeAction
{
    public function execute(User $user): void
    {
        // Use soft-delete so the user can be restored by admin if requested.
        $user->delete();
    }
}
