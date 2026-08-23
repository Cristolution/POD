<?php

declare(strict_types=1);

namespace App\Actions\Shipment;

use App\Models\Shipment;

class CreateShipmentAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): Shipment
    {
        $shipment = Shipment::create([
            'order_id' => $data['order_id'],
            'printer_provider_id' => $data['printer_provider_id'],
            'delivery_company_id' => $data['delivery_company_id'],
            'tracking_number' => $data['tracking_number'] ?? null,
            'status' => 'pending',
        ]);

        return $shipment->refresh();
    }
}
