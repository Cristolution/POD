<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_register_and_receive_token(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'password' => 'SecretPass1!',
            'password_confirmation' => 'SecretPass1!',
            'role' => 'customer',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['user' => ['id', 'name', 'role'], 'token']);

        $this->assertDatabaseHas('users', ['email' => 'ada@example.com', 'role' => 'customer']);
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_register_validation_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'dup@example.com']);

        $this->postJson('/api/auth/register', [
            'name' => 'X',
            'email' => 'dup@example.com',
            'password' => 'SecretPass1!',
            'password_confirmation' => 'SecretPass1!',
            'role' => 'customer',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_admin_role_cannot_be_self_assigned(): void
    {
        $this->postJson('/api/auth/register', [
            'name' => 'X',
            'email' => 'x@example.com',
            'password' => 'SecretPass1!',
            'password_confirmation' => 'SecretPass1!',
            'role' => 'admin',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('role');
    }
}
