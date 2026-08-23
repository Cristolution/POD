<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, User $target): bool
    {
        return $user->id === $target->id;
    }

    public function update(User $user, User $target): bool
    {
        return $user->id === $target->id;
    }

    public function delete(User $user, User $target): bool
    {
        return $user->id === $target->id;
    }

    public function restore(User $user): bool
    {
        return $user->isAdmin();
    }
}
