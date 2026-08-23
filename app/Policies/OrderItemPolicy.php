<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrderItemPolicy
{
    use HandlesAuthorization;

    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function view(User $user, OrderItem $item): bool
    {
        // The printer who is expected to fulfil this item…
        $printerProfileId = $user->printerProviderProfile?->id;

        if ($printerProfileId !== null && $item->printer_provider_id === $printerProfileId) {
            return true;
        }

        // …or the customer who placed the order.
        $order = $item->order()->first();

        return $order !== null && $order->customer_id === $user->id;
    }

    public function update(User $user, OrderItem $item): bool
    {
        if (! $user->isPrinterProvider()) {
            return false;
        }

        $printerProfileId = $user->printerProviderProfile?->id;

        return $printerProfileId !== null
            && $item->printer_provider_id === $printerProfileId;
    }

    public function reassign(User $user, OrderItem $item): bool
    {
        return false;
    }
}
