<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Catalog\Tags;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_is_forbidden(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $tag = Tag::factory()->create(['name' => 'old']);

        $this->actingAs($customer, 'sanctum')
            ->patchJson("/api/admin/tags/{$tag->id}", ['name' => 'new'])
            ->assertForbidden();
    }

    public function test_admin_can_rename_tag(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $tag = Tag::factory()->create(['name' => 'old']);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/tags/{$tag->id}", ['name' => 'new'])
            ->assertOk()
            ->assertJsonPath('data.id', $tag->id)
            ->assertJsonPath('data.name', 'new');

        $this->assertDatabaseHas('tags', ['id' => $tag->id, 'name' => 'new']);
    }
}
