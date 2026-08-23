<?php

declare(strict_types=1);

namespace App\Actions\Order;

use App\Models\Order;

class DeleteOrderAction
{
    public function execute(Order $order): void
    {
        $order->delete();
    }
}
