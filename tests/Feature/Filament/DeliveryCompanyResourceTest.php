<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Resources\DeliveryCompanies\Pages\CreateDeliveryCompany;
use App\Filament\Resources\DeliveryCompanies\Pages\ListDeliveryCompanies;
use App\Models\DeliveryCompany;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DeliveryCompanyResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_delivery_companies(): void
    {
        DeliveryCompany::factory()->count(3)->create();
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(ListDeliveryCompanies::class)
            ->assertSuccessful();
    }

    public function test_admin_can_create_delivery_company(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(CreateDeliveryCompany::class)
            ->fillForm([
                'name' => 'Acme Express',
                'coverage_zones' => ['US', 'EU', 'CA'],
                'tracking_url_pattern' => 'https://track.acme.com/{number}',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('delivery_companies', ['name' => 'Acme Express']);
    }
}
