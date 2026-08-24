<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Resources\Shipments\Pages\CreateShipment;
use App\Filament\Resources\Shipments\Pages\ListShipments;
use App\Models\DeliveryCompany;
use App\Models\Order;
use App\Models\PrinterProviderProfile;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ShipmentResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_shipments(): void
    {
        Shipment::factory()->count(3)->create();
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(ListShipments::class)
            ->assertSuccessful();
    }

    public function test_admin_can_create_shipment(): void
    {
        $order = Order::factory()->create();
        $printer = PrinterProviderProfile::factory()->create();
        $company = DeliveryCompany::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(CreateShipment::class)
            ->fillForm([
                'order_id' => (string) $order->id,
                'printer_provider_id' => (string) $printer->id,
                'delivery_company_id' => (string) $company->id,
                'tracking_number' => 'TRACK-12345',
                'status' => 'shipped',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('shipments', [
            'tracking_number' => 'TRACK-12345',
            'status' => 'shipped',
        ]);
    }
}
