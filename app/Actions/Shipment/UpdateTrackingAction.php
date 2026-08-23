<?php

declare(strict_types=1);

namespace App\Actions\Shipment;

use App\Models\Shipment;

class UpdateTrackingAction
{
    public function execute(Shipment $shipment, string $trackingNumber): Shipment
    {
        $shipment->tracking_number = $trackingNumber;
        $shipment->save();

        return $shipment->refresh();
    }
}
