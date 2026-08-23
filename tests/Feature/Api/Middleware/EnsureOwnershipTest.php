<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Middleware;

use App\Models\User;
use Database\Factories\AddressFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class EnsureOwnershipTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['auth:sanctum', 'owner:address'])->patch(
            '/api/_test/addresses/{address}/owner-only',
            fn () => response()->json(['ok' => true]),
        );
    }

    public function test_blocks_anonymous_request(): void
    {
        $address = AddressFactory::new()->create();

        $this->patchJson("/api/_test/addresses/{$address->id}/owner-only")
            ->assertStatus(401);
    }

    public function test_blocks_non_owner(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $address = AddressFactory::new()->create(['user_id' => $owner->id]);

        $this->actingAs($other, 'sanctum')
            ->patchJson("/api/_test/addresses/{$address->id}/owner-only")
            ->assertForbidden();
    }

    public function test_passes_owner(): void
    {
        $owner = User::factory()->create();
        $address = AddressFactory::new()->create(['user_id' => $owner->id]);

        $this->actingAs($owner, 'sanctum')
            ->patchJson("/api/_test/addresses/{$address->id}/owner-only")
            ->assertOk();
    }

    public function test_admin_always_passes(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $address = AddressFactory::new()->create(['user_id' => $owner->id]);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/_test/addresses/{$address->id}/owner-only")
            ->assertOk();
    }
}
