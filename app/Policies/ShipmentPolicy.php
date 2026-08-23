<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Shipment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ShipmentPolicy
{
    use HandlesAuthorization;

    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Shipment $shipment): bool
    {
        // Customer who owns the order…
        if ($shipment->order?->customer_id === $user->id) {
            return true;
        }

        // …or the assigned printer.
        $printerProfileId = $user->printerProviderProfile?->id;

        return $printerProfileId !== null
            && $shipment->printer_provider_id === $printerProfileId;
    }

    public function create(User $user): bool
    {
        return $user->isPrinterProvider();
    }

    public function update(User $user, Shipment $shipment): bool
    {
        if (! $user->isPrinterProvider()) {
            return false;
        }

        $printerProfileId = $user->printerProviderProfile?->id;

        return $printerProfileId !== null
            && $shipment->printer_provider_id === $printerProfileId;
    }

    public function markShipped(User $user, Shipment $shipment): bool
    {
        return $this->update($user, $shipment);
    }

    public function markDelivered(User $user, Shipment $shipment): bool
    {
        return $this->update($user, $shipment);
    }

    public function delete(User $user, Shipment $shipment): bool
    {
        // Admins short-circuit via before(); non-admins fall through here.
        return false;
    }
}
