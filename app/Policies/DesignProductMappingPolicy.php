<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\DesignProductMapping;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DesignProductMappingPolicy
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

    public function view(?User $user, DesignProductMapping $mapping): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isDesigner();
    }

    public function update(User $user, DesignProductMapping $mapping): bool
    {
        return $mapping->design?->designer?->user_id === $user->id;
    }

    public function delete(User $user, DesignProductMapping $mapping): bool
    {
        return $mapping->design?->designer?->user_id === $user->id;
    }
}
