<?php

declare(strict_types=1);

namespace App\Actions\Order;

use App\Models\Order;
use Illuminate\Support\Facades\DB;

class MarkOrderProcessingAction
{
    public function execute(Order $order): Order
    {
        return DB::transaction(function () use ($order): Order {
            $order->update(['status' => 'processing']);

            return $order->refresh()->load(['items', 'customer']);
        });
    }
}
