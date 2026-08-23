<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Designers;

use App\Models\Category;
use App\Models\User;
use Database\Factories\DesignerProfileFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_anonymous_can_list_designers(): void
    {
        DesignerProfileFactory::new()->count(3)->create();

        $this->getJson('/api/designers')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_designer_can_create_own_profile(): void
    {
        $user = User::factory()->create(['role' => 'designer']);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/me/designer-profile', ['bio' => 'Hello world'])
            ->assertCreated()
            ->assertJsonPath('data.bio', 'Hello world');

        $this->assertDatabaseHas('designer_profiles', ['user_id' => $user->id, 'bio' => 'Hello world']);
    }

    public function test_customer_cannot_create_designer_profile(): void
    {
        $user = User::factory()->create(['role' => 'customer']);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/me/designer-profile', ['bio' => 'Should fail'])
            ->assertForbidden();
    }

    public function test_designer_can_update_own_profile(): void
    {
        $user = User::factory()->create(['role' => 'designer']);
        $profile = DesignerProfileFactory::new()->for($user, 'user')->create();

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/me/designer-profile', ['bio' => 'Updated bio'])
            ->assertOk()
            ->assertJsonPath('data.bio', 'Updated bio');

        $this->assertSame('Updated bio', $profile->fresh()->bio);
    }

    public function test_designer_cannot_delete_profile_with_designs(): void
    {
        $user = User::factory()->create(['role' => 'designer']);
        $profile = DesignerProfileFactory::new()->for($user, 'user')->create();
        $category = Category::factory()->create();
        $profile->designs()->create([
            'designer_id' => $profile->id,
            'category_id' => $category->id,
            'title' => 'Test design',
            'status' => 'draft',
        ]);

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/me/designer-profile')
            ->assertStatus(409);
    }
}
