<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Resources\Addresses\Pages\CreateAddress;
use App\Filament\Resources\Addresses\Pages\ListAddresses;
use App\Models\Address;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AddressResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_addresses(): void
    {
        Address::factory()->count(3)->create();
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(ListAddresses::class)
            ->assertSuccessful();
    }

    public function test_admin_can_create_address(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(CreateAddress::class)
            ->fillForm([
                'user_id' => (string) $owner->id,
                'line1' => '12 Rue de la Paix',
                'city' => 'Paris',
                'country' => 'France',
                'phone' => '+33123456789',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('addresses', [
            'user_id' => $owner->id,
            'city' => 'Paris',
            'country' => 'France',
        ]);
    }
}
