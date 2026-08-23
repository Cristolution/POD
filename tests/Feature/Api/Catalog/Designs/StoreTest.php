<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Catalog\Designs;

use App\Models\Category;
use App\Models\User;
use Database\Factories\DesignerProfileFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_anonymous_cannot_create_design(): void
    {
        $category = Category::factory()->create();

        $this->postJson('/api/me/designs', [
            'category_id' => $category->id,
            'title' => 'Unauthed',
        ])
            ->assertUnauthorized();
    }

    public function test_customer_cannot_create_design(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $category = Category::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/me/designs', [
                'category_id' => $category->id,
                'title' => 'Should fail',
            ])
            ->assertForbidden();
    }

    public function test_designer_can_create_own_design(): void
    {
        $user = User::factory()->create(['role' => 'designer']);
        $profile = DesignerProfileFactory::new()->for($user, 'user')->create();
        $category = Category::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/me/designs', [
                'category_id' => $category->id,
                'title' => 'My fresh design',
                'status' => 'draft',
            ])
            ->assertCreated()
            ->assertJsonPath('data.title', 'My fresh design')
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.designer_id', $profile->id);

        $this->assertDatabaseHas('designs', [
            'designer_id' => $profile->id,
            'category_id' => $category->id,
            'title' => 'My fresh design',
            'status' => 'draft',
        ]);
    }

    public function test_store_validates_missing_title(): void
    {
        $user = User::factory()->create(['role' => 'designer']);
        DesignerProfileFactory::new()->for($user, 'user')->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/me/designs', [
                'status' => 'draft',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }
}
