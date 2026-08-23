<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        $methods = ['cash_on_delivery', 'bank_transfer', 'card'];

        return [
            'order_id' => Order::factory(),
            'method' => fake()->randomElement($methods),
            'status' => 'pending',
            'confirmed_by_admin_id' => null,
            'confirmed_at' => null,
        ];
    }

    public function confirmed(User $admin): static
    {
        return $this->state(fn () => [
            'status' => 'confirmed',
            'confirmed_by_admin_id' => $admin->id,
            'confirmed_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => ['status' => 'rejected']);
    }

    public function cashOnDelivery(): static
    {
        return $this->state(fn () => ['method' => 'cash_on_delivery']);
    }

    public function bankTransfer(): static
    {
        return $this->state(fn () => ['method' => 'bank_transfer']);
    }

    public function card(): static
    {
        return $this->state(fn () => ['method' => 'card']);
    }
}
