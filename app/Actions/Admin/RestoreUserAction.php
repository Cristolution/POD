<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Models\User;

class RestoreUserAction
{
    public function execute(User $target): User
    {
        $target->restore();

        return $target->refresh();
    }
}
