<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Models\OrderItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ItemsWithoutShipmentAction
{
    /**
     * Order items that have been handed off to the printer but for which no
     * shipment has been created against this order/printer pair.
     *
     * @return Collection<int, OrderItem>
     */
    public function execute(): Collection
    {
        return OrderItem::where('status', 'handed_off')
            ->whereNull('deleted_at')
            ->whereNotExists(function ($q): void {
                $q->select(DB::raw(1))
                    ->from('shipments')
                    ->whereColumn('shipments.order_id', 'order_items.order_id')
                    ->whereColumn('shipments.printer_provider_id', 'order_items.printer_provider_id');
            })
            ->with(['order', 'printerProvider'])
            ->get();
    }
}
