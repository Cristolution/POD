<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Design;
use App\Models\DesignReview;
use App\Models\User;

class DesignReviewPolicy
{
    /**
     * Anyone can view a single review — it's public.
     */
    public function view(User $user, DesignReview $designReview): bool
    {
        return $designReview->is_approved;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Customers can leave at most one review per design. We don't yet gate
     * on "must have purchased" — that's a future enhancement — but the
     * controller dedupes by (customer_id, design_id).
     */
    public function create(User $user, Design $design): bool
    {
        if (! $user->isCustomer()) {
            return false;
        }

        return $design->status === 'published'
            && $design->reviewBy($user) === null;
    }

    /**
     * A customer can delete their own review; admin can delete any.
     */
    public function delete(User $user, DesignReview $designReview): bool
    {
        return $user->isAdmin()
            || $designReview->customer_id === $user->getKey();
    }
}
