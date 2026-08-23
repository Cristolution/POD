<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Me;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_fetch_self(): void
    {
        $user = User::factory()->create(['role' => 'customer']);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.name', $user->name);
    }

    public function test_anonymous_is_rejected(): void
    {
        $this->getJson('/api/me')->assertStatus(401);
    }
}
