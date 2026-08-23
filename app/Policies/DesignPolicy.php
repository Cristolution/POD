<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Design;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DesignPolicy
{
    use HandlesAuthorization;

    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Design $design): bool
    {
        if ($design->status === 'published') {
            return true;
        }

        return $design->designer?->user_id === $user?->id;
    }

    public function create(User $user): bool
    {
        return $user->isDesigner();
    }

    public function update(User $user, Design $design): bool
    {
        return $design->designer?->user_id === $user->id;
    }

    public function delete(User $user, Design $design): bool
    {
        return $design->designer?->user_id === $user->id;
    }

    public function transfer(User $user, Design $design): bool
    {
        return $user->isAdmin();
    }
}
