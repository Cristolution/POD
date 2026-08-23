<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Catalog\Categories;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_is_forbidden(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $category = Category::factory()->create();

        $this->actingAs($customer, 'sanctum')
            ->patchJson("/api/admin/categories/{$category->id}", ['name' => 'Hats'])
            ->assertForbidden();
    }

    public function test_admin_can_update_category(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::factory()->create(['name' => 'Old Name']);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/categories/{$category->id}", ['name' => 'New Name'])
            ->assertOk()
            ->assertJsonPath('data.name', 'New Name');

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'New Name']);
    }

    public function test_admin_can_reparent_category(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $oldParent = Category::factory()->create();
        $newParent = Category::factory()->create();
        $category = Category::factory()->withParent($oldParent)->create();

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/categories/{$category->id}", ['parent_id' => $newParent->id])
            ->assertOk()
            ->assertJsonPath('data.parent_id', $newParent->id);
    }
}
