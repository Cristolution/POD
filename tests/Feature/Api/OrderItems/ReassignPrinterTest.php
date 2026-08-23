<?php

declare(strict_types=1);

namespace Tests\Feature\Api\OrderItems;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PrinterProviderProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReassignPrinterTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_reassign_pending_item(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $oldPrinter = PrinterProviderProfile::factory()->create();
        $newPrinter = PrinterProviderProfile::factory()->create();

        $order = Order::factory()->pending()->create();
        $item = OrderItem::factory()->create([
            'order_id' => $order->id,
            'printer_provider_id' => $oldPrinter->id,
            'status' => 'pending',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->patchJson(
                "/api/admin/orders/{$order->id}/items/{$item->id}/printer",
                ['printer_provider_id' => $newPrinter->id],
            )
            ->assertOk()
            ->assertJsonPath('data.printer_provider_id', $newPrinter->id);

        $this->assertDatabaseHas('order_items', [
            'id' => $item->id,
            'printer_provider_id' => $newPrinter->id,
        ]);
    }

    public function test_cannot_reassign_non_pending_item(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $oldPrinter = PrinterProviderProfile::factory()->create();
        $newPrinter = PrinterProviderProfile::factory()->create();

        $order = Order::factory()->pending()->create();
        $item = OrderItem::factory()->create([
            'order_id' => $order->id,
            'printer_provider_id' => $oldPrinter->id,
            'status' => 'received',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->patchJson(
                "/api/admin/orders/{$order->id}/items/{$item->id}/printer",
                ['printer_provider_id' => $newPrinter->id],
            )
            ->assertStatus(409);

        $this->assertDatabaseHas('order_items', [
            'id' => $item->id,
            'printer_provider_id' => $oldPrinter->id,
        ]);
    }

    public function test_customer_cannot_reassign(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $oldPrinter = PrinterProviderProfile::factory()->create();
        $newPrinter = PrinterProviderProfile::factory()->create();

        $order = Order::factory()->pending()->create();
        $item = OrderItem::factory()->create([
            'order_id' => $order->id,
            'printer_provider_id' => $oldPrinter->id,
            'status' => 'pending',
        ]);

        $this->actingAs($customer, 'sanctum')
            ->patchJson(
                "/api/admin/orders/{$order->id}/items/{$item->id}/printer",
                ['printer_provider_id' => $newPrinter->id],
            )
            ->assertForbidden();

        $this->assertDatabaseHas('order_items', [
            'id' => $item->id,
            'printer_provider_id' => $oldPrinter->id,
        ]);
    }
}
