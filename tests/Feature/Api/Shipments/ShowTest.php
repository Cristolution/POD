<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Shipments;

use App\Models\DeliveryCompany;
use App\Models\Order;
use App\Models\PrinterProviderProfile;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_own_shipment(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->pending()->forCustomer($customer)->create();
        $company = DeliveryCompany::factory()->create();
        $shipment = Shipment::factory()->create([
            'order_id' => $order->id,
            'delivery_company_id' => $company->id,
        ]);

        $this->actingAs($customer, 'sanctum')
            ->getJson("/api/shipments/{$shipment->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $shipment->id);
    }

    public function test_other_customer_gets_403(): void
    {
        $owner = User::factory()->create(['role' => 'customer']);
        $stranger = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->pending()->forCustomer($owner)->create();
        $company = DeliveryCompany::factory()->create();
        $shipment = Shipment::factory()->create([
            'order_id' => $order->id,
            'delivery_company_id' => $company->id,
        ]);

        $this->actingAs($stranger, 'sanctum')
            ->getJson("/api/shipments/{$shipment->id}")
            ->assertForbidden();
    }

    public function test_assigned_printer_can_view(): void
    {
        $printer = User::factory()->create(['role' => 'printer_provider']);
        $printerProfile = PrinterProviderProfile::factory()->create(['user_id' => $printer->id]);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->pending()->forCustomer($customer)->create();
        $company = DeliveryCompany::factory()->create();
        $shipment = Shipment::factory()->create([
            'order_id' => $order->id,
            'printer_provider_id' => $printerProfile->id,
            'delivery_company_id' => $company->id,
        ]);

        $this->actingAs($printer, 'sanctum')
            ->getJson("/api/shipments/{$shipment->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $shipment->id);
    }

    public function test_unrelated_printer_gets_403(): void
    {
        $assignedPrinter = User::factory()->create(['role' => 'printer_provider']);
        $assignedProfile = PrinterProviderProfile::factory()->create(['user_id' => $assignedPrinter->id]);

        $otherPrinter = User::factory()->create(['role' => 'printer_provider']);
        $otherProfile = PrinterProviderProfile::factory()->create(['user_id' => $otherPrinter->id]);

        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->pending()->forCustomer($customer)->create();
        $company = DeliveryCompany::factory()->create();
        $shipment = Shipment::factory()->create([
            'order_id' => $order->id,
            'printer_provider_id' => $assignedProfile->id,
            'delivery_company_id' => $company->id,
        ]);

        $this->actingAs($otherPrinter, 'sanctum')
            ->getJson("/api/shipments/{$shipment->id}")
            ->assertForbidden();
    }
}
