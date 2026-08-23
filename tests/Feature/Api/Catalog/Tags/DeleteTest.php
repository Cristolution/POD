<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Catalog\Tags;

use App\Models\Design;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_is_forbidden(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $tag = Tag::factory()->create();

        $this->actingAs($customer, 'sanctum')
            ->deleteJson("/api/admin/tags/{$tag->id}")
            ->assertForbidden();
    }

    public function test_admin_can_delete_unattached_tag(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $tag = Tag::factory()->create(['name' => 'unattached']);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/admin/tags/{$tag->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
    }

    public function test_admin_cannot_delete_tag_attached_to_design(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $tag = Tag::factory()->create(['name' => 'attached']);
        $design = Design::factory()->create();
        $tag->designs()->attach($design->id);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/admin/tags/{$tag->id}")
            ->assertStatus(409);

        $this->assertDatabaseHas('tags', ['id' => $tag->id]);
    }
}
