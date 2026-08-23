<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Catalog\Categories;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_anonymous_is_rejected(): void
    {
        $this->postJson('/api/admin/categories', ['name' => 'T-Shirts'])
            ->assertStatus(401);
    }

    public function test_customer_is_forbidden(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/admin/categories', ['name' => 'T-Shirts'])
            ->assertForbidden();
    }

    public function test_admin_can_create_category(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/categories', ['name' => 'T-Shirts'])
            ->assertCreated()
            ->assertJsonPath('data.name', 'T-Shirts');

        $this->assertDatabaseHas('categories', ['name' => 'T-Shirts']);
    }

    public function test_admin_can_create_child_category(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $parent = Category::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/categories', [
                'name' => 'Long Sleeve',
                'parent_id' => $parent->id,
            ])
            ->assertCreated()
            ->assertJsonPath('data.parent_id', $parent->id);
    }

    public function test_validation_rejects_missing_name(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/categories', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }
}
