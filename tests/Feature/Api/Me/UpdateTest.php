<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Me;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_name_and_phone(): void
    {
        $user = User::factory()->create(['role' => 'customer']);

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/me', [
                'name' => 'New Name',
                'phone' => '+15551234567',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'New Name')
            ->assertJsonPath('data.phone', '+15551234567');
    }

    public function test_password_update_requires_current_password(): void
    {
        $user = User::factory()->create([
            'role' => 'customer',
            'password' => Hash::make('OldPass1!'),
        ]);

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/me', [
                'current_password' => 'WRONG',
                'password' => 'NewPass2!',
                'password_confirmation' => 'NewPass2!',
            ])
            ->assertStatus(422);
    }

    public function test_password_update_succeeds_with_correct_current_password(): void
    {
        $user = User::factory()->create([
            'role' => 'customer',
            'password' => Hash::make('OldPass1!'),
        ]);

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/me/password', [
                'current_password' => 'OldPass1!',
                'password' => 'NewPass2!',
                'password_confirmation' => 'NewPass2!',
            ])
            ->assertNoContent();

        $this->assertTrue(Hash::check('NewPass2!', $user->fresh()->password));
    }
}
