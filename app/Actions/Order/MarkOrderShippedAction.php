<?php

declare(strict_types=1);

namespace App\Actions\Order;

use App\Models\Order;
use App\Notifications\OrderShippedNotification;
use Illuminate\Support\Facades\DB;

class MarkOrderShippedAction
{
    public function execute(Order $order): Order
    {
        $order = DB::transaction(function () use ($order): Order {
            $order->update(['status' => 'shipped']);

            // Cascade to order items — printer has handed every unit off.
            $order->items()->update(['status' => 'handed_off']);

            return $order->refresh()->load(['items', 'customer']);
        });

        $order->customer?->notify(new OrderShippedNotification($order));

        return $order;
    }
}
