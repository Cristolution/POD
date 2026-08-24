<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SettingPolicy
{
    use HandlesAuthorization;

    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(?User $user): bool
    {
        // Public reads.
        return true;
    }

    public function view(?User $user, Setting $setting): bool
    {
        // Public reads.
        return true;
    }

    public function create(User $user): bool
    {
        // Admin writes only — gated by `before()` returning true.
        return false;
    }

    public function update(User $user, Setting $setting): bool
    {
        return false;
    }

    public function delete(User $user, Setting $setting): bool
    {
        return false;
    }
}
