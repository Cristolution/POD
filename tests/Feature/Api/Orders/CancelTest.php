<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Orders;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CancelTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_cancel_pending_order(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->pending()->forCustomer($user)->create();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/orders/{$order->id}/cancel")
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'cancelled']);
    }

    public function test_double_cancel_returns_409(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->pending()->forCustomer($user)->create();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/orders/{$order->id}/cancel")
            ->assertOk();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/orders/{$order->id}/cancel")
            ->assertStatus(409);
    }

    public function test_cannot_cancel_delivered_order(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->delivered()->forCustomer($user)->create();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/orders/{$order->id}/cancel")
            ->assertStatus(409);
    }

    public function test_non_owner_cannot_cancel(): void
    {
        $owner = User::factory()->create(['role' => 'customer']);
        $stranger = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->pending()->forCustomer($owner)->create();

        $this->actingAs($stranger, 'sanctum')
            ->postJson("/api/orders/{$order->id}/cancel")
            ->assertForbidden();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'pending']);
    }
}
