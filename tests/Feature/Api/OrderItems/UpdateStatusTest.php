<?php

declare(strict_types=1);

namespace Tests\Feature\Api\OrderItems;

use App\Models\DesignProductMapping;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PrinterProviderProfile;
use App\Models\ProductTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_printer_can_accept_pending_item(): void
    {
        // Create the printer user and profile.
        $printerUser = User::factory()->printerProvider()->create();
        $printerProfile = PrinterProviderProfile::factory()->create([
            'user_id' => $printerUser->id,
        ]);

        // Build an OrderItem whose printer_provider_id points at that profile.
        $template = ProductTemplate::factory()->create(['printer_provider_id' => $printerProfile->id]);
        $mapping = DesignProductMapping::factory()->create([
            'product_template_id' => $template->id,
        ]);
        $order = Order::factory()->pending()->create();
        $item = OrderItem::factory()->create([
            'order_id' => $order->id,
            'design_product_mapping_id' => $mapping->id,
            'printer_provider_id' => $printerProfile->id,
            'status' => 'pending',
        ]);

        $this->actingAs($printerUser, 'sanctum')
            ->patchJson("/api/order-items/{$item->id}/status", ['status' => 'received'])
            ->assertOk()
            ->assertJsonPath('data.status', 'received');

        $this->assertDatabaseHas('order_items', ['id' => $item->id, 'status' => 'received']);
    }

    public function test_invalid_transition_returns_409(): void
    {
        $printerUser = User::factory()->printerProvider()->create();
        $printerProfile = PrinterProviderProfile::factory()->create(['user_id' => $printerUser->id]);

        $order = Order::factory()->pending()->create();
        $item = OrderItem::factory()->create([
            'order_id' => $order->id,
            'printer_provider_id' => $printerProfile->id,
            'status' => 'pending',
        ]);

        // pending → printed is NOT allowed (only 'received' or 'cancelled').
        $this->actingAs($printerUser, 'sanctum')
            ->patchJson("/api/order-items/{$item->id}/status", ['status' => 'printed'])
            ->assertStatus(409);

        $this->assertDatabaseHas('order_items', ['id' => $item->id, 'status' => 'pending']);
    }

    public function test_non_owner_printer_cannot_update(): void
    {
        $ownerPrinterUser = User::factory()->printerProvider()->create();
        $ownerProfile = PrinterProviderProfile::factory()->create(['user_id' => $ownerPrinterUser->id]);

        $strangerPrinterUser = User::factory()->printerProvider()->create();
        PrinterProviderProfile::factory()->create(['user_id' => $strangerPrinterUser->id]);

        $order = Order::factory()->pending()->create();
        $item = OrderItem::factory()->create([
            'order_id' => $order->id,
            'printer_provider_id' => $ownerProfile->id,
            'status' => 'pending',
        ]);

        $this->actingAs($strangerPrinterUser, 'sanctum')
            ->patchJson("/api/order-items/{$item->id}/status", ['status' => 'received'])
            ->assertForbidden();

        $this->assertDatabaseHas('order_items', ['id' => $item->id, 'status' => 'pending']);
    }
}
