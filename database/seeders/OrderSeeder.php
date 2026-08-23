<?php

namespace Database\Seeders;

use App\Models\DeliveryCompany;
use App\Models\DesignProductMapping;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $customers = User::where('role', 'customer')->get();
        $deliveryCompanies = DeliveryCompany::all();

        foreach ($customers as $customer) {
            // 70% of customers have 1-3 orders
            if (! fake()->boolean(70)) {
                continue;
            }

            $orderCount = fake()->numberBetween(1, 3);
            $customerAddress = $customer->addresses->first();

            for ($i = 0; $i < $orderCount; $i++) {
                $status = fake()->randomElement(['pending', 'paid', 'processing', 'shipped', 'delivered', 'cancelled']);

                /** @var Order $order */
                $order = Order::factory()->for($customer, 'customer')->create([
                    'status' => $status,
                ]);

                // Snapshot shipping address if the customer has one
                if ($customerAddress) {
                    $order->update([
                        'shipping_address_id' => $customerAddress->id,
                        'shipping_line1' => $customerAddress->line1,
                        'shipping_city' => $customerAddress->city,
                        'shipping_country' => $customerAddress->country,
                        'shipping_phone' => $customerAddress->phone,
                    ]);
                }

                // 1-4 items per order, possibly from multiple printers
                $mappings = DesignProductMapping::inRandomOrder()
                    ->take(fake()->numberBetween(1, 4))
                    ->get();

                $total = 0;

                foreach ($mappings as $mapping) {
                    $linePrice = (float) $mapping->final_price;
                    $quantity = fake()->numberBetween(1, 3);
                    $lineTotal = $linePrice * $quantity;

                    OrderItem::factory()->create([
                        'order_id' => $order->id,
                        'design_product_mapping_id' => $mapping->id,
                        'product_variant_id' => null,
                        'printer_provider_id' => $mapping->preferred_printer_id,
                        'status' => match ($status) {
                            'shipped', 'delivered' => fake()->randomElement(['printed', 'handed_off']),
                            'processing' => fake()->randomElement(['received', 'printing', 'printed']),
                            'paid' => fake()->randomElement(['pending', 'received']),
                            'cancelled' => 'cancelled',
                            default => 'pending',
                        },
                        'quantity' => $quantity,
                        'unit_price' => $linePrice,
                    ]);

                    $total += $lineTotal;
                }

                $order->update(['total_amount' => round($total, 2)]);

                // Create one shipment per printer involved in the order
                if (in_array($status, ['shipped', 'delivered'], true)) {
                    $order->items()
                        ->with('printerProvider')
                        ->get()
                        ->groupBy('printer_provider_id')
                        ->each(function ($orderItems, $printerId) use ($order, $deliveryCompanies) {
                            $shipment = Shipment::factory()->create([
                                'order_id' => $order->id,
                                'printer_provider_id' => $printerId,
                                'delivery_company_id' => $deliveryCompanies->random()->id,
                            ]);

                            $shippedAt = null;
                            if ($shipment->status === 'shipped' || $shipment->status === 'delivered') {
                                $shippedAt = fake()->dateTimeBetween('-30 days', '-7 days');
                                $shipment->update(['shipped_at' => $shippedAt]);
                            }

                            if ($shipment->status === 'delivered' && $shippedAt) {
                                $shipment->update([
                                    'delivered_at' => fake()->dateTimeBetween($shippedAt, 'now'),
                                ]);
                            }
                        });
                }
            }
        }
    }
}
