<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OrderResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_orders(): void
    {
        Order::factory()->count(3)->create();
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(ListOrders::class)
            ->assertSuccessful();
    }

    public function test_order_resource_has_no_create_route(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // canCreate() returns false on OrderResource — orders are read-only.
        $this->assertFalse(OrderResource::canCreate());
    }

    public function test_admin_can_cancel_order_via_action(): void
    {
        $order = Order::factory()->pending()->create();
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(ViewOrder::class, ['record' => $order->id])
            ->assertSuccessful()
            ->callAction('cancel');

        $this->assertSame('cancelled', $order->fresh()->status);
    }

    public function test_admin_can_mark_shipped_order_delivered(): void
    {
        $order = Order::factory()->shipped()->create();
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(ViewOrder::class, ['record' => $order->id])
            ->assertSuccessful()
            ->callAction('markDelivered');

        $this->assertSame('delivered', $order->fresh()->status);
    }
}
