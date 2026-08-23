<?php

declare(strict_types=1);

namespace App\Actions\Shipment;

use App\Events\OrderShipped;
use App\Models\Shipment;
use App\Notifications\OrderShippedNotification;

class MarkShippedAction
{
    public function execute(Shipment $shipment): Shipment
    {
        abort_unless($shipment->isPending(), 409, 'Only pending shipments can be marked shipped.');

        $shipment->status = 'shipped';
        $shipment->shipped_at = now();
        $shipment->save();

        $shipment = $shipment->refresh();

        OrderShipped::dispatch($shipment->order);
        $shipment->order->customer?->notify(new OrderShippedNotification($shipment->order));

        return $shipment;
    }
}
