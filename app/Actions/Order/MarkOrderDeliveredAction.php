<?php

declare(strict_types=1);

namespace App\Actions\Order;

use App\Models\Order;
use App\Notifications\OrderDeliveredNotification;
use Illuminate\Support\Facades\DB;

class MarkOrderDeliveredAction
{
    public function execute(Order $order): Order
    {
        $order = DB::transaction(function () use ($order): Order {
            $order->update(['status' => 'delivered']);

            // Cascade — every line item has reached its terminal state.
            $order->items()->update(['status' => 'printed']);

            return $order->refresh()->load(['items', 'customer']);
        });

        $order->customer?->notify(new OrderDeliveredNotification($order));

        return $order;
    }
}
