<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Models\User;

class DeleteUserAction
{
    public function execute(User $target): void
    {
        $target->delete();
    }
}
