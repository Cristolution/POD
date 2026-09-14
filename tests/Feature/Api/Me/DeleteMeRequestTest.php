<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Me;

use App\Mail\DeleteAccountConfirmationMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class DeleteMeRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_receives_202_and_confirmation_email_is_sent(): void
    {
        Mail::fake();

        $user = User::factory()->create(['role' => 'customer']);

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/me')
            ->assertStatus(202);

        Mail::assertSent(DeleteAccountConfirmationMail::class, fn (DeleteAccountConfirmationMail $mail): bool => $mail->hasTo($user->email));

        // The user record is NOT yet deleted — only a confirmation link was sent.
        $this->assertNull($user->fresh()->deleted_at);
    }

    public function test_anonymous_is_rejected(): void
    {
        $this->deleteJson('/api/me')->assertStatus(401);
    }

    public function test_already_deleted_user_gets_410(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $user->delete(); // soft delete

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/me')
            ->assertStatus(410);
    }
}
