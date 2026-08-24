<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    // ------------------------------------------------------------------
    // Login
    // ------------------------------------------------------------------

    public function test_get_login_renders_form_for_guests(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Log in')
            ->assertSee('Forgot your password?')
            ->assertSee('Sign up');
    }

    public function test_get_login_redirects_authenticated_users_home(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('login'))
            ->assertRedirect(route('home'));
    }

    public function test_post_login_with_valid_credentials_authenticates_and_redirects(): void
    {
        User::factory()->create([
            'email' => 'login@example.com',
            'password' => Hash::make('SecretPass1!'),
            'role' => 'customer',
        ]);

        $response = $this->post(route('login'), [
            'email' => 'login@example.com',
            'password' => 'SecretPass1!',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs(User::where('email', 'login@example.com')->first());
    }

    public function test_post_login_with_wrong_password_shows_validation_error(): void
    {
        User::factory()->create([
            'email' => 'login@example.com',
            'password' => Hash::make('SecretPass1!'),
            'role' => 'customer',
        ]);

        $this->from(route('login'))
            ->post(route('login'), [
                'email' => 'login@example.com',
                'password' => 'wrong-password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    // ------------------------------------------------------------------
    // Register
    // ------------------------------------------------------------------

    public function test_get_register_renders_form_for_guests(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('Sign up')
            ->assertSee('Create account');
    }

    public function test_post_register_creates_user_and_authenticates(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'New Customer',
            'email' => 'new@example.com',
            'password' => 'SecretPass1!',
            'password_confirmation' => 'SecretPass1!',
        ]);

        $user = User::where('email', 'new@example.com')->first();

        $this->assertNotNull($user, 'User was not created.');
        $this->assertSame('customer', $user->role);
        $this->assertTrue(Hash::check('SecretPass1!', $user->password));

        $response->assertRedirect(route('home'));
        $response->assertSessionHas('status', 'Welcome!');
        $this->assertAuthenticatedAs($user);
    }

    public function test_post_register_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'dup@example.com']);

        $this->from(route('register'))
            ->post(route('register'), [
                'name' => 'Dup',
                'email' => 'dup@example.com',
                'password' => 'SecretPass1!',
                'password_confirmation' => 'SecretPass1!',
            ])
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors('email');

        $this->assertSame(1, User::where('email', 'dup@example.com')->count());
        $this->assertGuest();
    }

    // ------------------------------------------------------------------
    // Password reset (forgot / link / update)
    // ------------------------------------------------------------------

    public function test_get_forgot_password_renders_form(): void
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('Forgot')
            ->assertSee('Email reset link');
    }

    public function test_post_forgot_password_with_unknown_email_shows_error(): void
    {
        Notification::fake();

        $this->from(route('password.request'))
            ->post(route('password.email'), [
                'email' => 'nobody@example.com',
            ])
            ->assertRedirect(route('password.request'))
            ->assertSessionHasErrors('email');

        Notification::assertNothingSent();
    }

    public function test_get_reset_password_renders_form_with_token(): void
    {
        $this->get(route('password.reset', 'sample-token'))
            ->assertOk()
            ->assertSee('Reset password')
            ->assertSee('value="sample-token"', escape: false);
    }

    public function test_post_reset_password_with_valid_token_succeeds(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'reset@example.com',
            'password' => Hash::make('OldPass1!'),
        ]);

        $token = Password::broker()->createToken($user);

        $response = $this->post(route('password.store'), [
            'token' => $token,
            'email' => 'reset@example.com',
            'password' => 'NewPass1!',
            'password_confirmation' => 'NewPass1!',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status');

        $user->refresh();
        $this->assertTrue(Hash::check('NewPass1!', $user->password));
        $this->assertFalse(Hash::check('OldPass1!', $user->password));
    }

    public function test_post_forgot_password_sends_reset_notification(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'notify@example.com',
        ]);

        $this->post(route('password.email'), [
            'email' => 'notify@example.com',
        ]);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    // ------------------------------------------------------------------
    // Logout
    // ------------------------------------------------------------------

    public function test_post_logout_when_authenticated_logs_out_and_redirects_home(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('web.logout'))
            ->assertRedirect(route('home'));

        $this->assertGuest();
        $this->assertNull(Auth::user());
    }

    public function test_post_logout_when_anonymous_redirects_to_login(): void
    {
        $this->post(route('web.logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
