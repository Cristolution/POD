<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ProductTemplate;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProductTemplatePolicy
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

    public function view(?User $user, ProductTemplate $template): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isPrinterProvider();
    }

    public function update(User $user, ProductTemplate $template): bool
    {
        return $template->printerProvider?->user_id === $user->id;
    }

    public function delete(User $user, ProductTemplate $template): bool
    {
        return $template->printerProvider?->user_id === $user->id;
    }
}
