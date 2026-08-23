<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Middleware;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class EnsureUserRoleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Stub a test-only endpoint that uses both middleware.
        // Tests in this file are the only consumers of /api/_test/admin-dashboard.
        Route::middleware(['auth:sanctum', 'role:admin'])->get(
            '/api/_test/admin-dashboard',
            fn () => response()->json(['total_customers' => 0, 'total_designers' => 0]),
        );

        Route::middleware(['auth:sanctum', 'role:designer'])->get(
            '/api/_test/designer-only',
            fn () => response()->json(['ok' => true]),
        );
    }

    public function test_blocks_anonymous_request(): void
    {
        $this->getJson('/api/_test/admin-dashboard')->assertStatus(401);
    }

    public function test_blocks_wrong_role(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer, 'sanctum')
            ->getJson('/api/_test/admin-dashboard')
            ->assertForbidden();

        $this->actingAs($customer, 'sanctum')
            ->getJson('/api/_test/designer-only')
            ->assertForbidden();
    }

    public function test_passes_correct_role(): void
    {
        $designer = User::factory()->create(['role' => 'designer']);

        $this->actingAs($designer, 'sanctum')
            ->getJson('/api/_test/designer-only')
            ->assertOk()
            ->assertJson(['ok' => true]);
    }

    public function test_admin_always_passes_regardless_of_role_listing(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/_test/designer-only')
            ->assertOk();
    }
}
