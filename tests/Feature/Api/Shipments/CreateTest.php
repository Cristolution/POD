<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Shipments;

use App\Models\DeliveryCompany;
use App\Models\DesignProductMapping;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PrinterProviderProfile;
use App\Models\ProductTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_printer_can_create_shipment_for_own_order(): void
    {
        $printer = User::factory()->create(['role' => 'printer_provider']);
        $printerProfile = PrinterProviderProfile::factory()->create(['user_id' => $printer->id]);
        $customer = User::factory()->create(['role' => 'customer']);

        ProductTemplate::factory()->create();
        $mapping = DesignProductMapping::factory()->create();

        $order = Order::factory()->pending()->forCustomer($customer)->create();
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'design_product_mapping_id' => $mapping->id,
            'printer_provider_id' => $printerProfile->id,
        ]);

        $company = DeliveryCompany::factory()->create();

        $this->actingAs($printer, 'sanctum')
            ->postJson('/api/shipments', [
                'order_id' => $order->id,
                'printer_provider_id' => $printerProfile->id,
                'delivery_company_id' => $company->id,
                'tracking_number' => 'TR-12345',
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.order_id', $order->id)
            ->assertJsonPath('data.printer_provider_id', $printerProfile->id)
            ->assertJsonPath('data.tracking_number', 'TR-12345');

        $this->assertDatabaseHas('shipments', [
            'order_id' => $order->id,
            'printer_provider_id' => $printerProfile->id,
            'delivery_company_id' => $company->id,
            'status' => 'pending',
        ]);
    }

    public function test_customer_cannot_create_shipment(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $printerUser = User::factory()->create(['role' => 'printer_provider']);
        $printerProfile = PrinterProviderProfile::factory()->create(['user_id' => $printerUser->id]);

        $order = Order::factory()->pending()->forCustomer($customer)->create();
        $company = DeliveryCompany::factory()->create();

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/shipments', [
                'order_id' => $order->id,
                'printer_provider_id' => $printerProfile->id,
                'delivery_company_id' => $company->id,
            ])
            ->assertForbidden();
    }

    public function test_validation_fails_on_unknown_order(): void
    {
        $printer = User::factory()->create(['role' => 'printer_provider']);
        $printerProfile = PrinterProviderProfile::factory()->create(['user_id' => $printer->id]);
        $company = DeliveryCompany::factory()->create();

        $this->actingAs($printer, 'sanctum')
            ->postJson('/api/shipments', [
                'order_id' => '00000000-0000-0000-0000-000000000000',
                'printer_provider_id' => $printerProfile->id,
                'delivery_company_id' => $company->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('order_id');
    }
}
