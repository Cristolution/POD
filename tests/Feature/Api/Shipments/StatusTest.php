<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Shipments;

use App\Models\DeliveryCompany;
use App\Models\Order;
use App\Models\PrinterProviderProfile;
use App\Models\Shipment;
use App\Models\User;
use App\Notifications\OrderDeliveredNotification;
use App\Notifications\OrderShippedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class StatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_printer_can_mark_pending_shipment_shipped(): void
    {
        Notification::fake();

        $printer = User::factory()->create(['role' => 'printer_provider']);
        $printerProfile = PrinterProviderProfile::factory()->create(['user_id' => $printer->id]);
        $customer = User::factory()->create(['role' => 'customer']);
        $company = DeliveryCompany::factory()->create();

        $order = Order::factory()->pending()->forCustomer($customer)->create();
        $shipment = Shipment::factory()->create([
            'order_id' => $order->id,
            'printer_provider_id' => $printerProfile->id,
            'delivery_company_id' => $company->id,
            'status' => 'pending',
        ]);

        $this->actingAs($printer, 'sanctum')
            ->patchJson("/api/shipments/{$shipment->id}/mark-shipped")
            ->assertOk()
            ->assertJsonPath('data.status', 'shipped');

        $this->assertNotNull($shipment->fresh()->shipped_at);
        Notification::assertSentTo($customer, OrderShippedNotification::class);
    }

    public function test_cannot_ship_already_shipped(): void
    {
        $printer = User::factory()->create(['role' => 'printer_provider']);
        $printerProfile = PrinterProviderProfile::factory()->create(['user_id' => $printer->id]);
        $customer = User::factory()->create(['role' => 'customer']);
        $company = DeliveryCompany::factory()->create();

        $order = Order::factory()->pending()->forCustomer($customer)->create();
        $shipment = Shipment::factory()->shipped()->create([
            'order_id' => $order->id,
            'printer_provider_id' => $printerProfile->id,
            'delivery_company_id' => $company->id,
        ]);

        $this->actingAs($printer, 'sanctum')
            ->patchJson("/api/shipments/{$shipment->id}/mark-shipped")
            ->assertStatus(409);
    }

    public function test_printer_can_mark_shipped_as_delivered(): void
    {
        Notification::fake();

        $printer = User::factory()->create(['role' => 'printer_provider']);
        $printerProfile = PrinterProviderProfile::factory()->create(['user_id' => $printer->id]);
        $customer = User::factory()->create(['role' => 'customer']);
        $company = DeliveryCompany::factory()->create();

        $order = Order::factory()->pending()->forCustomer($customer)->create();
        $shipment = Shipment::factory()->shipped()->create([
            'order_id' => $order->id,
            'printer_provider_id' => $printerProfile->id,
            'delivery_company_id' => $company->id,
        ]);

        $this->actingAs($printer, 'sanctum')
            ->patchJson("/api/shipments/{$shipment->id}/mark-delivered")
            ->assertOk()
            ->assertJsonPath('data.status', 'delivered');

        $this->assertNotNull($shipment->fresh()->delivered_at);
        Notification::assertSentTo($customer, OrderDeliveredNotification::class);
    }

    public function test_cannot_deliver_pending_shipment(): void
    {
        $printer = User::factory()->create(['role' => 'printer_provider']);
        $printerProfile = PrinterProviderProfile::factory()->create(['user_id' => $printer->id]);
        $customer = User::factory()->create(['role' => 'customer']);
        $company = DeliveryCompany::factory()->create();

        $order = Order::factory()->pending()->forCustomer($customer)->create();
        $shipment = Shipment::factory()->create([
            'order_id' => $order->id,
            'printer_provider_id' => $printerProfile->id,
            'delivery_company_id' => $company->id,
            'status' => 'pending',
        ]);

        $this->actingAs($printer, 'sanctum')
            ->patchJson("/api/shipments/{$shipment->id}/mark-delivered")
            ->assertStatus(409);
    }
}
