<?php

declare(strict_types=1);

namespace App\Actions\Payment;

use App\Models\Order;
use App\Models\Payment;

class CreatePaymentForOrderAction
{
    public function execute(Order $order, string $method): Payment
    {
        $payment = Payment::create([
            'order_id' => $order->id,
            'method' => $method,
            'status' => 'pending',
        ]);

        return $payment->refresh();
    }
}
