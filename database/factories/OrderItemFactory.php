<?php

namespace Database\Factories;

use App\Models\DesignProductMapping;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PrinterProviderProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    public function definition(): array
    {
        $statuses = ['pending', 'received', 'printing', 'printed', 'handed_off', 'cancelled'];

        return [
            'order_id' => Order::factory(),
            'design_product_mapping_id' => DesignProductMapping::factory(),
            'product_variant_id' => null,
            'printer_provider_id' => PrinterProviderProfile::factory(),
            'status' => fake()->randomElement($statuses),
            'quantity' => fake()->numberBetween(1, 5),
            'unit_price' => fake()->randomFloat(2, 10, 100),
        ];
    }
}
