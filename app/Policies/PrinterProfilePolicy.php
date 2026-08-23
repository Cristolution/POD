<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PrinterProviderProfile;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PrinterProfilePolicy
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

    public function view(?User $user, PrinterProviderProfile $profile): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isPrinterProvider();
    }

    public function update(User $user, PrinterProviderProfile $profile): bool
    {
        return $profile->user_id === $user->id;
    }

    public function delete(User $user, PrinterProviderProfile $profile): bool
    {
        return $profile->user_id === $user->id;
    }
}
