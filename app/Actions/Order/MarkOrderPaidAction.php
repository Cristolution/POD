<?php

declare(strict_types=1);

namespace App\Actions\Order;

use App\Models\Order;
use App\Notifications\OrderPaidNotification;
use Illuminate\Support\Facades\DB;

class MarkOrderPaidAction
{
    public function execute(Order $order): Order
    {
        $order = DB::transaction(function () use ($order): Order {
            $order->update(['status' => 'paid']);

            // Cascade to order items — printer can now begin fulfilment.
            $order->items()->update(['status' => 'received']);

            return $order->refresh()->load(['items', 'customer']);
        });

        // Customer — payment confirmed.
        $order->customer?->notify(new OrderPaidNotification($order));

        return $order;
    }
}
