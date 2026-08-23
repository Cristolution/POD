<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Payments;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_pending_payment_for_order(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->pending()->forCustomer($customer)->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/orders/{$order->id}/payments", [
                'order_id' => $order->id,
                'method' => 'cash_on_delivery',
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.method', 'cash_on_delivery')
            ->assertJsonPath('data.order_id', $order->id);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'method' => 'cash_on_delivery',
            'status' => 'pending',
        ]);
    }

    public function test_customer_cannot_create(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->pending()->forCustomer($customer)->create();

        $this->actingAs($customer, 'sanctum')
            ->postJson("/api/orders/{$order->id}/payments", [
                'order_id' => $order->id,
                'method' => 'cash_on_delivery',
            ])
            ->assertForbidden();
    }

    public function test_validation_rejects_bad_method(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->pending()->forCustomer($customer)->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/orders/{$order->id}/payments", [
                'order_id' => $order->id,
                'method' => 'western_union',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('method');
    }
}
