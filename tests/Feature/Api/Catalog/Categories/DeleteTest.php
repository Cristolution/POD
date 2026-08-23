<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Catalog\Categories;

use App\Models\Category;
use App\Models\Design;
use App\Models\DesignerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_is_forbidden(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $category = Category::factory()->create();

        $this->actingAs($customer, 'sanctum')
            ->deleteJson("/api/admin/categories/{$category->id}")
            ->assertForbidden();
    }

    public function test_admin_can_delete_empty_category(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/admin/categories/{$category->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_admin_cannot_delete_category_with_designs(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $designer = User::factory()->create(['role' => 'designer']);
        $profile = DesignerProfile::factory()->for($designer, 'user')->create();
        $category = Category::factory()->create();
        Design::factory()->for($profile, 'designer')->for($category, 'category')->create();

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/admin/categories/{$category->id}")
            ->assertStatus(409);

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }
}
