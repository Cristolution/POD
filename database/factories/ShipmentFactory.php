<?php

namespace Database\Factories;

use App\Models\DeliveryCompany;
use App\Models\Order;
use App\Models\PrinterProviderProfile;
use App\Models\Shipment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Shipment>
 */
class ShipmentFactory extends Factory
{
    protected $model = Shipment::class;

    public function definition(): array
    {
        $statuses = ['pending', 'shipped', 'delivered', 'returned'];

        return [
            'order_id' => Order::factory(),
            'printer_provider_id' => PrinterProviderProfile::factory(),
            'delivery_company_id' => DeliveryCompany::factory(),
            'tracking_number' => strtoupper(fake()->unique()->bothify('TR-##########')),
            'status' => fake()->randomElement($statuses),
            'shipped_at' => null,
            'delivered_at' => null,
        ];
    }

    public function shipped(): static
    {
        return $this->state(fn () => [
            'status' => 'shipped',
            'shipped_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ]);
    }

    public function delivered(): static
    {
        return $this->state(fn () => [
            'status' => 'delivered',
            'shipped_at' => fake()->dateTimeBetween('-30 days', '-7 days'),
            'delivered_at' => fake()->dateTimeBetween('-7 days', 'now'),
        ]);
    }
}
