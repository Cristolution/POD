<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class NotificationPolicy
{
    use HandlesAuthorization;

    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Notification $notification): bool
    {
        return $this->isOwner($notification, $user);
    }

    public function create(User $user): bool
    {
        // Admin broadcasts are gated by `role:admin` middleware. The
        // notification resource itself is created via the DB channel.
        return false;
    }

    public function markRead(User $user, Notification $notification): bool
    {
        return $this->isOwner($notification, $user);
    }

    public function update(User $user, Notification $notification): bool
    {
        return $this->isOwner($notification, $user);
    }

    public function delete(User $user, Notification $notification): bool
    {
        return $this->isOwner($notification, $user);
    }

    private function isOwner(Notification $notification, User $user): bool
    {
        return (string) $notification->notifiable_id === (string) $user->id
            && $notification->notifiable_type === $user->getMorphClass();
    }
}
