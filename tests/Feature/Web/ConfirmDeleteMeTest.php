<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\NewAccessToken;
use Tests\TestCase;

class ConfirmDeleteMeTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_signature_soft_deletes_user_and_revokes_tokens(): void
    {
        $user = User::factory()->create(['role' => 'customer']);

        // Give the user a Sanctum token that should be revoked on delete.
        /** @var NewAccessToken $token */
        $token = $user->createToken('web');

        $signedUrl = URL::temporarySignedRoute(
            'me.delete.confirm',
            now()->addMinutes(30),
            ['id' => $user->getKey()],
        );

        $this->get($signedUrl)
            ->assertRedirect(route('account.deleted'));

        $user->refresh();
        $this->assertNotNull($user->deleted_at, 'User should be soft-deleted.');
        $this->assertCount(0, $user->fresh()->tokens, 'Sanctum tokens should be revoked.');
    }

    public function test_invalid_signature_returns_403(): void
    {
        $user = User::factory()->create(['role' => 'customer']);

        $this->get("/me/delete/confirm/{$user->id}?expires=9999999999&signature=invalid")
            ->assertStatus(403);

        $this->assertNull($user->fresh()->deleted_at);
    }

    public function test_after_confirm_getting_me_returns_401(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        /** @var NewAccessToken $token */
        $token = $user->createToken('web');

        $signedUrl = URL::temporarySignedRoute(
            'me.delete.confirm',
            now()->addMinutes(30),
            ['id' => $user->getKey()],
        );

        $this->get($signedUrl)->assertRedirect(route('account.deleted'));

        // After delete, the previously-issued Sanctum token must no longer
        // authenticate — because the user it belongs to is soft-deleted.
        $this->withToken($token->plainTextToken)
            ->getJson('/api/me')
            ->assertStatus(401);
    }

    public function test_cross_user_signature_replay_returns_403(): void
    {
        $alice = User::factory()->create(['role' => 'customer']);
        $mallory = User::factory()->create(['role' => 'customer']);

        // Mallory signs a URL for himself but substitutes Alice's id.
        $signedUrl = URL::temporarySignedRoute(
            'me.delete.confirm',
            now()->addMinutes(30),
            ['id' => $mallory->getKey()],
        );

        // Replace mallory's id with alice's id (the signature no longer matches).
        $tampered = preg_replace(
            '#/me/delete/confirm/[^?]+#',
            "/me/delete/confirm/{$alice->id}",
            $signedUrl,
        );

        $this->get($tampered)->assertStatus(403);

        $this->assertNull($alice->fresh()->deleted_at);
        $this->assertNull($mallory->fresh()->deleted_at);
    }
}
