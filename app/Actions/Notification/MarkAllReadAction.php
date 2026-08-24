<?php

declare(strict_types=1);

namespace App\Actions\Notification;

use App\Models\User;

class MarkAllReadAction
{
    public function execute(User $user): int
    {
        return $user->notifications()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}
