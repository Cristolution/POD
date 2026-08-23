<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Addresses;

use App\Models\Address;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_anonymous_is_rejected(): void
    {
        $this->getJson('/api/me/addresses')->assertStatus(401);
    }

    public function test_user_can_create_address(): void
    {
        $user = User::factory()->create(['role' => 'customer']);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/me/addresses', [
                'line1' => '12 Rue de la Paix',
                'city' => 'Paris',
                'country' => 'FR',
            ])
            ->assertCreated()
            ->assertJsonPath('data.line1', '12 Rue de la Paix')
            ->assertJsonPath('data.country', 'FR');

        $this->assertDatabaseHas('addresses', ['user_id' => $user->id, 'country' => 'FR']);
    }

    public function test_country_must_be_iso_alpha2(): void
    {
        $user = User::factory()->create(['role' => 'customer']);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/me/addresses', [
                'line1' => '123 Main',
                'city' => 'NYC',
                'country' => 'USA',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('country');
    }

    public function test_user_cannot_view_another_users_address(): void
    {
        $owner = User::factory()->create(['role' => 'customer']);
        $other = User::factory()->create(['role' => 'customer']);
        $address = Address::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($other, 'sanctum')
            ->getJson("/api/me/addresses/{$address->id}")
            ->assertForbidden();
    }

    public function test_user_can_update_and_delete_own_address(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $address = Address::factory()->create(['user_id' => $user->id, 'city' => 'Paris']);

        $this->actingAs($user, 'sanctum')
            ->patchJson("/api/me/addresses/{$address->id}", ['city' => 'Lyon'])
            ->assertOk()
            ->assertJsonPath('data.city', 'Lyon');

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/me/addresses/{$address->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('addresses', ['id' => $address->id]);
    }
}
