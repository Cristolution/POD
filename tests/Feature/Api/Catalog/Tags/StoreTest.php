<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Catalog\Tags;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_anonymous_is_rejected(): void
    {
        $this->postJson('/api/admin/tags', ['name' => 'modern'])
            ->assertStatus(401);
    }

    public function test_customer_is_forbidden(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/admin/tags', ['name' => 'modern'])
            ->assertForbidden();
    }

    public function test_admin_can_create_tag(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/tags', ['name' => 'modern'])
            ->assertCreated()
            ->assertJsonPath('data.name', 'modern');

        $this->assertDatabaseHas('tags', ['name' => 'modern']);
    }

    public function test_creation_requires_name(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/tags', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    public function test_creation_rejects_duplicate_name(): void
    {
        Tag::factory()->create(['name' => 'vintage']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/tags', ['name' => 'vintage'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }
}
