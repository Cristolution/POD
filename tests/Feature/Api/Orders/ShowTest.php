<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Orders;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_order(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->pending()->forCustomer($user)->create();

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/orders/{$order->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $order->id)
            ->assertJsonPath('data.customer_id', $user->id);
    }

    public function test_non_owner_gets_403(): void
    {
        $owner = User::factory()->create(['role' => 'customer']);
        $stranger = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->pending()->forCustomer($owner)->create();

        $this->actingAs($stranger, 'sanctum')
            ->getJson("/api/orders/{$order->id}")
            ->assertForbidden();
    }

    public function test_admin_can_view_any_order(): void
    {
        $owner = User::factory()->create(['role' => 'customer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $order = Order::factory()->pending()->forCustomer($owner)->create();

        $this->actingAs($admin, 'sanctum')
            ->getJson("/api/orders/{$order->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $order->id);
    }
}
