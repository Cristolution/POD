<?php

declare(strict_types=1);

namespace App\Actions\Order;

use App\Models\Order;

class CancelOrderAction
{
    public function execute(Order $order): Order
    {
        abort_if($order->status === 'cancelled', 409, 'Order is already cancelled.');
        abort_if($order->status === 'delivered', 409, 'Delivered orders cannot be cancelled.');

        $order->status = 'cancelled';
        $order->save();

        return $order->refresh();
    }
}
