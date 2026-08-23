<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Users;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_anonymous_is_rejected(): void
    {
        $this->getJson('/api/admin/users')->assertStatus(401);
    }

    public function test_non_admin_is_forbidden(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer, 'sanctum')
            ->getJson('/api/admin/users')
            ->assertForbidden();
    }

    public function test_admin_can_list_users_filtered_by_role(): void
    {
        User::factory()->count(3)->create(['role' => 'customer']);
        User::factory()->count(2)->create(['role' => 'designer']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/users?role=designer')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_user_can_be_soft_deleted_and_restored(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $target = User::factory()->create(['role' => 'customer']);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/admin/users/{$target->id}")
            ->assertNoContent();

        $this->assertSoftDeleted('users', ['id' => $target->id]);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/admin/users/{$target->id}/restore")
            ->assertOk()
            ->assertJsonPath('data.id', $target->id);

        $this->assertDatabaseHas('users', ['id' => $target->id, 'deleted_at' => null]);
    }
}
