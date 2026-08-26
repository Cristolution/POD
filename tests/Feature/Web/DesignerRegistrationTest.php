<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Models\DesignerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DesignerRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_register_as_designer_creates_user_with_role_and_profile(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'New Designer',
            'email' => 'designer@example.com',
            'password' => 'SecretPass1!',
            'password_confirmation' => 'SecretPass1!',
            'role' => 'designer',
        ]);

        $user = User::where('email', 'designer@example.com')->first();

        $this->assertNotNull($user, 'User was not created.');
        $this->assertSame('designer', $user->role);
        $this->assertNotNull($user->designerProfile, 'DesignerProfile was not created.');
        $this->assertTrue($user->designerProfile->is_verified, 'New designer must default to verified.');

        // Authenticated + redirected to the designer dashboard.
        $response->assertRedirect(route('designer.dashboard'));
        $this->assertAuthenticatedAs($user);
        $response->assertSessionHas('status');
    }

    public function test_post_register_as_customer_does_not_create_designer_profile(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'Plain Customer',
            'email' => 'customer@example.com',
            'password' => 'SecretPass1!',
            'password_confirmation' => 'SecretPass1!',
            'role' => 'customer',
        ]);

        $user = User::where('email', 'customer@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame('customer', $user->role);
        $this->assertNull($user->designerProfile);
        $this->assertSame(0, DesignerProfile::query()->count());

        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_post_register_without_role_defaults_to_customer(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'Default',
            'email' => 'default@example.com',
            'password' => 'SecretPass1!',
            'password_confirmation' => 'SecretPass1!',
        ]);

        $user = User::where('email', 'default@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame('customer', $user->role);
        $this->assertNull($user->designerProfile);

        $response->assertRedirect(route('home'));
    }

    public function test_post_register_with_admin_role_is_rejected(): void
    {
        $response = $this->from(route('register'))
            ->post(route('register'), [
                'name' => 'Sneaky Admin',
                'email' => 'admin@example.com',
                'password' => 'SecretPass1!',
                'password_confirmation' => 'SecretPass1!',
                'role' => 'admin',
            ]);

        $response->assertRedirect(route('register'));
        $response->assertSessionHasErrors('role');
        $this->assertNull(User::where('email', 'admin@example.com')->first());
        $this->assertGuest();
    }

    public function test_get_register_renders_role_selector(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('I want to sign up as')
            ->assertSee('value="customer"', escape: false)
            ->assertSee('value="designer"', escape: false);
    }
}
