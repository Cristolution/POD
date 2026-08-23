<?php

declare(strict_types=1);

namespace App\Actions\Shipment;

use App\Models\Shipment;

class DeleteShipmentAction
{
    public function execute(Shipment $shipment): void
    {
        $shipment->delete();
    }
}
