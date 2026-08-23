<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Orders;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_soft_delete_order(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $order = Order::factory()->pending()->create();

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/admin/orders/{$order->id}")
            ->assertNoContent();

        $this->assertSoftDeleted('orders', ['id' => $order->id]);
    }

    public function test_admin_can_restore_order(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $order = Order::factory()->pending()->create();
        $order->delete();

        $this->assertSoftDeleted('orders', ['id' => $order->id]);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/admin/orders/{$order->id}/restore")
            ->assertOk()
            ->assertJsonPath('data.id', $order->id)
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'deleted_at' => null]);
    }
}
