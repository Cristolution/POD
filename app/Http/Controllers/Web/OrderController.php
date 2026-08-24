<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function show(Request $request, Order $order): View
    {
        // Order uses `customer_id` (not `user_id`) as its user FK.
        abort_unless($order->customer_id === $request->user()->id, 403);

        $order->load([
            'items.designProductMapping.design.media',
            'items.designProductMapping.productTemplate',
            'items.productVariant',
            'items.printerProvider',
            'shippingAddress',
            'payments',
        ]);

        return view('pages.orders.show', ['order' => $order]);
    }
}
