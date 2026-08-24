<?php

declare(strict_types=1);

namespace App\Actions\Notification;

use App\Models\User;
use App\Notifications\AdminBroadcastNotification;

class BroadcastNotificationAction
{
    /**
     * @param  array<int, string>  $targetUserIds
     */
    public function execute(User $adminActor, string $type, string $message, array $targetUserIds): int
    {
        $targets = User::query()
            ->whereIn('id', $targetUserIds)
            ->get();

        $notification = new AdminBroadcastNotification(
            type: $type,
            message: $message,
            adminActorId: $adminActor->id,
        );

        foreach ($targets as $user) {
            $user->notify($notification);
        }

        return $targets->count();
    }
}
