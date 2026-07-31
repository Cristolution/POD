<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $statuses = ['pending', 'paid', 'processing', 'shipped', 'delivered', 'cancelled'];

        return [
            'customer_id' => User::factory(),
            'shipping_address_id' => null,
            'shipping_line1' => fake()->streetAddress(),
            'shipping_city' => fake()->city(),
            'shipping_country' => fake()->country(),
            'shipping_phone' => fake()->e164PhoneNumber(),
            'status' => fake()->randomElement($statuses),
            'total_amount' => 0, // overridden by seeder after items are added
        ];
    }

    public function forCustomer(User $user): static
    {
        return $this->state(fn () => ['customer_id' => $user->id]);
    }

    public function withShippingAddress(Address $address): static
    {
        return $this->state(fn () => [
            'shipping_address_id' => $address->id,
            'shipping_line1' => $address->line1,
            'shipping_city' => $address->city,
            'shipping_country' => $address->country,
            'shipping_phone' => $address->phone,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => 'pending']);
    }

    public function paid(): static
    {
        return $this->state(fn () => ['status' => 'paid']);
    }

    public function processing(): static
    {
        return $this->state(fn () => ['status' => 'processing']);
    }

    public function shipped(): static
    {
        return $this->state(fn () => ['status' => 'shipped']);
    }

    public function delivered(): static
    {
        return $this->state(fn () => ['status' => 'delivered']);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => 'cancelled']);
    }
}
