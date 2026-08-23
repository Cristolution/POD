<?php

declare(strict_types=1);

namespace App\Actions\Order;

use App\Models\OrderItem;

class ReassignPrinterAction
{
    public function execute(OrderItem $item, string $newPrinterProviderId): OrderItem
    {
        abort_unless(
            $item->status === 'pending',
            409,
            'Only pending order items can be reassigned to a different printer.'
        );

        $item->printer_provider_id = $newPrinterProviderId;
        $item->save();

        return $item->refresh();
    }
}
