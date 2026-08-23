<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\DeliveryCompany;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DeliveryCompanyPolicy
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

    public function view(?User $user, DeliveryCompany $company): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        // Admins short-circuit via before(); non-admins fall through here.
        return false;
    }

    public function update(User $user, DeliveryCompany $company): bool
    {
        return false;
    }

    public function delete(User $user, DeliveryCompany $company): bool
    {
        return false;
    }
}
