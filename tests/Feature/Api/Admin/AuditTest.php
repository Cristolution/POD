<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_see_deleted_users(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $deleted = User::factory()->create(['role' => 'customer']);
        $deleted->delete();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/audit/deleted')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $deleted->id);
    }

    public function test_customer_cannot_see_audit(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer, 'sanctum')
            ->getJson('/api/admin/audit/deleted')
            ->assertForbidden();
    }
}
