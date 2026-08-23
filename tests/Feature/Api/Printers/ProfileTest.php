<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Printers;

use App\Models\User;
use Database\Factories\PrinterProviderProfileFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_printer_can_create_own_profile(): void
    {
        $user = User::factory()->create(['role' => 'printer_provider']);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/me/printer-profile', ['company_name' => 'Acme Print'])
            ->assertCreated()
            ->assertJsonPath('data.company_name', 'Acme Print');

        $this->assertDatabaseHas('printer_provider_profiles', [
            'user_id' => $user->id,
            'company_name' => 'Acme Print',
        ]);
    }

    public function test_designer_cannot_create_printer_profile(): void
    {
        $user = User::factory()->create(['role' => 'designer']);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/me/printer-profile', ['company_name' => 'Should fail'])
            ->assertForbidden();
    }

    public function test_printer_can_update_company_name(): void
    {
        $user = User::factory()->create(['role' => 'printer_provider']);
        $profile = PrinterProviderProfileFactory::new()->for($user, 'user')->create();

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/me/printer-profile', ['company_name' => 'Renamed Inc'])
            ->assertOk()
            ->assertJsonPath('data.company_name', 'Renamed Inc');

        $this->assertSame('Renamed Inc', $profile->fresh()->company_name);
    }
}
