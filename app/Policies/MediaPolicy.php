<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Design;
use App\Models\Media;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\ProductTemplate;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MediaPolicy
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

    public function view(?User $user, Media $media): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        return $this->isOwnerOf($media, $user);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Media $media): bool
    {
        return $this->isOwnerOf($media, $user);
    }

    public function delete(User $user, Media $media): bool
    {
        return $this->isOwnerOf($media, $user);
    }

    /**
     * The polymorphic owner may be a model that links to a User via direct
     * `user_id`/`customer_id` (Order, DesignerProfile, PrinterProviderProfile,
     * User) or via a hop through a related profile / order.
     */
    private function isOwnerOf(Media $media, User $user): bool
    {
        $owner = $media->model;

        if ($owner === null) {
            return false;
        }

        // Direct fields first.
        foreach (['user_id', 'customer_id'] as $field) {
            if (isset($owner->{$field}) && (string) $owner->{$field} === (string) $user->id) {
                return true;
            }
        }

        // Designer-owned models: design.designer.user_id.
        if ($owner instanceof Design) {
            return (string) $owner->designer?->user_id === (string) $user->id;
        }

        // Payment-owned: payment.order.customer_id.
        if ($owner instanceof Payment) {
            return (string) $owner->order?->customer_id === (string) $user->id;
        }

        // OrderItem-owned: order_item.order.customer_id.
        if ($owner instanceof OrderItem) {
            return (string) $owner->order?->customer_id === (string) $user->id;
        }

        // Printer template / variant — owned by the printer user.
        if ($owner instanceof ProductTemplate) {
            return (string) $owner->printerProvider?->user_id === (string) $user->id;
        }

        if ($owner instanceof ProductVariant) {
            return (string) $owner->productTemplate?->printerProvider?->user_id === (string) $user->id;
        }

        return false;
    }
}
