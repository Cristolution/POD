<?php

declare(strict_types=1);

namespace App\Actions\Order;

use App\Models\Order;

class RestoreOrderAction
{
    public function execute(Order $order): Order
    {
        $order->restore();

        return $order->refresh();
    }
}
