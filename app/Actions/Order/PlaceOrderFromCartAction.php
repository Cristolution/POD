<?php

declare(strict_types=1);

namespace App\Actions\Order;

use App\Actions\Payment\CreatePaymentForOrderAction;
use App\Events\OrderPlaced;
use App\Models\Address;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PrinterProviderProfile;
use App\Models\User;
use App\Notifications\DesignerOrderReceivedNotification;
use App\Notifications\OrderPlacedNotification;
use App\Notifications\PrinterFulfilmentRequestedNotification;
use Illuminate\Support\Facades\DB;

class PlaceOrderFromCartAction
{
    public function __construct(
        private readonly CreatePaymentForOrderAction $createPayment,
    ) {}

    public function execute(User $user, int $shippingAddressId, string $paymentMethod = 'cash_on_delivery'): Order
    {
        $order = DB::transaction(function () use ($user, $shippingAddressId, $paymentMethod): Order {
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

            return $order->refresh()->load(['items', 'shippingAddress', 'customer', 'payments']);
        });

        // Fire events + notifications OUTSIDE the transaction so a failure in
        // notification delivery doesn't roll back the order.
        OrderPlaced::dispatch($order);

        // 1. Customer — order confirmation
        $user->notify(new OrderPlacedNotification($order));

        // 2. Each designer whose design is in this order — grouped so a single
        // cart touching 3 of their designs notifies them once, not three times.
        $designerItems = $order->items
            ->load('designProductMapping.design.designer.user')
            ->groupBy(fn (OrderItem $item) => $item->designProductMapping?->design?->designer_id)
            ->filter(fn ($items, $designerId) => $designerId !== null);

        foreach ($designerItems as $designerId => $designerOrderItems) {
            $designer = $designerOrderItems->first()->designProductMapping->design->designer;
            $designerUser = $designer?->user;
            if ($designerUser === null) {
                continue;
            }

            $lines = $designerOrderItems->map(fn (OrderItem $item) => [
                'title' => $item->designProductMapping->design->title,
                'quantity' => $item->quantity,
                'unit_price' => (float) $item->unit_price,
            ])->all();

            $designerUser->notify(new DesignerOrderReceivedNotification($order, $lines));
        }

        // 3. Each printer who owns a template referenced in this order.
        $printerItems = $order->items
            ->groupBy(fn (OrderItem $item) => $item->printer_provider_id)
            ->filter(fn ($items, $printerId) => $printerId !== null);

        foreach ($printerItems as $printerId => $printerOrderItems) {
            /** @var PrinterProviderProfile|null $printerProfile */
            $printerProfile = PrinterProviderProfile::query()->with('user')->find($printerId);
            $printerUser = $printerProfile?->user;
            if ($printerUser === null) {
                continue;
            }

            $lines = $printerOrderItems->map(fn (OrderItem $item) => [
                'order_item_id' => $item->id,
                'product' => $item->designProductMapping->productTemplate->type ?? 'Product',
                'variant' => $item->productVariant?->label(),
                'quantity' => $item->quantity,
                'unit_price' => (float) $item->unit_price,
            ])->all();

            $printerUser->notify(new PrinterFulfilmentRequestedNotification($order, $lines));
        }

        return $order;
    }
}
