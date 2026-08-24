<?php

declare(strict_types=1);

namespace App\Actions\Order;

use App\Actions\Payment\CreatePaymentForOrderAction;
use App\Events\OrderPlaced;
use App\Models\Address;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Notifications\OrderPlacedNotification;
use Illuminate\Support\Facades\DB;

class PlaceOrderFromCartAction
{
    public function __construct(
        private readonly CreatePaymentForOrderAction $createPayment,
    ) {}

    public function execute(User $user, int $shippingAddressId, string $paymentMethod = 'cash_on_delivery'): Order
    {
        return DB::transaction(function () use ($user, $shippingAddressId, $paymentMethod): Order {
            $items = $user->cartItems()
                ->with(['designProductMapping.productTemplate.printerProvider', 'productVariant'])
                ->get();

            abort_if($items->isEmpty(), 422, 'Your cart is empty.');

            /** @var Address $address */
            $address = Address::query()
                ->where('user_id', $user->id)
                ->whereKey($shippingAddressId)
                ->first();

            abort_if($address === null, 422, 'The selected shipping address does not belong to you.');

            $subtotal = round((float) $items->sum(fn ($item) => $item->lineTotal()), 2);

            $order = Order::create([
                'customer_id' => $user->id,
                'shipping_address_id' => $address->id,
                'shipping_line1' => $address->line1,
                'shipping_city' => $address->city,
                'shipping_country' => $address->country,
                'shipping_phone' => $address->phone,
                'status' => 'pending',
                'total_amount' => $subtotal,
            ]);

            foreach ($items as $cartItem) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'design_product_mapping_id' => $cartItem->design_product_mapping_id,
                    'product_variant_id' => $cartItem->product_variant_id,
                    'printer_provider_id' => $cartItem->designProductMapping?->productTemplate?->printer_provider_id,
                    'status' => 'pending',
                    'quantity' => $cartItem->quantity,
                    'unit_price' => $cartItem->unitPrice(),
                ]);
            }

            // Record the chosen payment method in the same transaction so a
            // partial failure rolls the order back together with the payment.
            $this->createPayment->execute($order, $paymentMethod);

            // Clear the cart in the same transaction so a partial failure rolls everything back.
            $user->cartItems()->delete();

            $order = $order->refresh()->load(['items', 'shippingAddress', 'customer', 'payments']);

            OrderPlaced::dispatch($order);
            $user->notify(new OrderPlacedNotification($order));

            return $order;
        });
    }
}
