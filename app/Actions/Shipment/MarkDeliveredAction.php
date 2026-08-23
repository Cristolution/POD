<?php

declare(strict_types=1);

namespace App\Actions\Shipment;

use App\Events\OrderDelivered;
use App\Models\Shipment;
use App\Notifications\OrderDeliveredNotification;

class MarkDeliveredAction
{
    public function execute(Shipment $shipment): Shipment
    {
        abort_unless($shipment->isShipped(), 409, 'Only shipped shipments can be marked delivered.');

        $shipment->status = 'delivered';
        $shipment->delivered_at = now();
        $shipment->save();

        $shipment = $shipment->refresh();

        OrderDelivered::dispatch($shipment->order);
        $shipment->order->customer?->notify(new OrderDeliveredNotification($shipment->order));

        return $shipment;
    }
}
