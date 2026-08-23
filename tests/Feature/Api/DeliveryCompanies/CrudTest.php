<?php

declare(strict_types=1);

namespace Tests\Feature\Api\DeliveryCompanies;

use App\Models\DeliveryCompany;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_delivery_company(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/delivery-companies', [
                'name' => 'Acme Logistics',
                'coverage_zones' => ['US', 'CA'],
                'tracking_url_pattern' => 'https://acme.com/track/{number}',
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Acme Logistics')
            ->assertJsonPath('data.coverage_zones', ['US', 'CA']);

        $this->assertDatabaseHas('delivery_companies', ['name' => 'Acme Logistics']);
    }

    public function test_admin_can_update_delivery_company(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $company = DeliveryCompany::factory()->create(['name' => 'Old Name']);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/delivery-companies/{$company->id}", [
                'name' => 'New Name',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'New Name');

        $this->assertDatabaseHas('delivery_companies', ['id' => $company->id, 'name' => 'New Name']);
    }

    public function test_admin_can_delete_unused_company(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $company = DeliveryCompany::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/admin/delivery-companies/{$company->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('delivery_companies', ['id' => $company->id]);
    }

    public function test_customer_cannot_create(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/admin/delivery-companies', [
                'name' => 'Should Fail',
            ])
            ->assertForbidden();
    }

    public function test_anonymous_cannot_create(): void
    {
        $this->postJson('/api/admin/delivery-companies', [
            'name' => 'Should Fail',
        ])->assertStatus(401);
    }
}
