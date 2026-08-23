<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProductVariantPolicy
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

    public function view(?User $user, ProductVariant $variant): bool
    {
        return true;
    }

    public function create(User $user, ProductVariant $variant): bool
    {
        return $variant->productTemplate?->printerProvider?->user_id === $user->id;
    }

    public function update(User $user, ProductVariant $variant): bool
    {
        return $variant->productTemplate?->printerProvider?->user_id === $user->id;
    }

    public function delete(User $user, ProductVariant $variant): bool
    {
        return $variant->productTemplate?->printerProvider?->user_id === $user->id;
    }
}
