<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Approvals;

use App\Filament\Pages\Approvals\PendingOrdersPage;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PendingOrdersPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_pending_orders_queue(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        Order::factory()->forCustomer($customer)->create(['status' => 'pending']);

        Livewire::actingAs($admin)
            ->test(PendingOrdersPage::class)
            ->assertSuccessful()
            ->assertSee($customer->name)
            ->assertSee('pending');
    }

    public function test_only_open_orders_appear_in_queue(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Order::factory()->count(2)->create(['status' => 'pending']);
        Order::factory()->count(1)->create(['status' => 'paid']);
        Order::factory()->count(1)->create(['status' => 'processing']);
        Order::factory()->count(1)->create(['status' => 'shipped']);
        Order::factory()->count(2)->create(['status' => 'delivered']);
        Order::factory()->count(1)->create(['status' => 'cancelled']);

        $component = Livewire::actingAs($admin)->test(PendingOrdersPage::class);

        $this->assertCount(5, $component->get('orders'));
    }

    public function test_admin_can_advance_pending_to_paid(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $order = Order::factory()->create(['status' => 'pending']);

        Livewire::actingAs($admin)
            ->test(PendingOrdersPage::class)
            ->call('advanceOrder', $order->id);

        $this->assertSame('paid', $order->fresh()->status);
    }

    public function test_admin_can_advance_paid_to_processing(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $order = Order::factory()->create(['status' => 'paid']);

        Livewire::actingAs($admin)
            ->test(PendingOrdersPage::class)
            ->call('advanceOrder', $order->id);

        $this->assertSame('processing', $order->fresh()->status);
    }

    public function test_admin_can_advance_processing_to_shipped(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $order = Order::factory()->create(['status' => 'processing']);

        Livewire::actingAs($admin)
            ->test(PendingOrdersPage::class)
            ->call('advanceOrder', $order->id);

        $this->assertSame('shipped', $order->fresh()->status);
    }

    public function test_admin_can_advance_shipped_to_delivered(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $order = Order::factory()->create(['status' => 'shipped']);

        Livewire::actingAs($admin)
            ->test(PendingOrdersPage::class)
            ->call('advanceOrder', $order->id);

        $this->assertSame('delivered', $order->fresh()->status);
    }

    public function test_queue_refreshes_after_advance(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $order = Order::factory()->create(['status' => 'pending']);

        $component = Livewire::actingAs($admin)->test(PendingOrdersPage::class);
        $this->assertCount(1, $component->get('orders'));

        $component->call('advanceOrder', $order->id);

        // Order advanced from pending → paid, both of which are in the
        // open-orders set — so the queue still has 1 row, just in a new state.
        $this->assertCount(1, $component->get('orders'));
        $this->assertSame('paid', $component->get('orders')->first()->status);
    }

    public function test_advancing_to_delivered_removes_order_from_queue(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $order = Order::factory()->create(['status' => 'shipped']);

        $component = Livewire::actingAs($admin)->test(PendingOrdersPage::class);
        $this->assertCount(1, $component->get('orders'));

        $component->call('advanceOrder', $order->id);

        // delivered is terminal — the order leaves the open queue.
        $this->assertCount(0, $component->get('orders'));
    }

    public function test_non_admin_cannot_reach_pending_orders(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer)
            ->get('/admin/approvals-orders')
            ->assertForbidden();
    }
}
