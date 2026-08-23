<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_token_for_valid_credentials(): void
    {
        User::factory()->create([
            'email' => 'login@example.com',
            'password' => Hash::make('SecretPass1!'),
            'role' => 'customer',
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'login@example.com',
            'password' => 'SecretPass1!',
            'device_name' => 'phpunit',
        ])
            ->assertOk()
            ->assertJsonStructure(['user' => ['id'], 'token']);
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        $this->postJson('/api/auth/login', [
            'email' => 'nope@example.com',
            'password' => 'wrong',
        ])->assertStatus(401);
    }

    public function test_logout_revokes_current_token(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $token = $user->createToken('phpunit')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/auth/logout')
            ->assertNoContent();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
