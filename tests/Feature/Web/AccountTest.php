<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Models\Address;
use App\Models\Design;
use App\Models\DesignerProfile;
use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PrinterProviderProfile;
use App\Models\ProductTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    // ------------------------------------------------------------------
    // Dashboard / auth gate
    // ------------------------------------------------------------------

    public function test_anonymous_user_is_redirected_to_login_on_dashboard(): void
    {
        $this->get(route('account.dashboard'))->assertRedirect(route('login'));
    }

    public function test_anonymous_user_is_redirected_to_login_on_addresses(): void
    {
        $this->get(route('account.addresses.index'))->assertRedirect(route('login'));
    }

    public function test_anonymous_user_is_redirected_to_login_on_orders(): void
    {
        $this->get(route('account.orders'))->assertRedirect(route('login'));
    }

    public function test_anonymous_user_is_redirected_to_login_on_notifications(): void
    {
        $this->get(route('account.notifications'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_sees_dashboard_with_recent_orders(): void
    {
        $user = User::factory()->create(['name' => 'Pat Customer']);
        $address = Address::factory()->for($user)->create();
        Order::factory()
            ->forCustomer($user)
            ->withShippingAddress($address)
            ->pending()
            ->create(['total_amount' => 42.50]);

        $this->actingAs($user)
            ->get(route('account.dashboard'))
            ->assertOk()
            ->assertSee('Pat Customer')
            ->assertSee('Profile')
            ->assertSee('Addresses')
            ->assertSee('Orders')
            ->assertSee('Notifications')
            ->assertSee('pending');
    }

    // ------------------------------------------------------------------
    // Role-aware dashboard: /account renders the right view per role so the
    // header's user-name link lands designers and printers back on their
    // role-specific dashboard from anywhere in the app.
    // ------------------------------------------------------------------

    public function test_account_dashboard_renders_designer_view_for_designer(): void
    {
        $user = User::factory()->designer()->create(['name' => 'Grace Hopper']);
        $profile = DesignerProfile::factory()->for($user)->create();
        Design::factory()->forDesigner($profile)->published()->create(['title' => 'Neon Bloom']);

        $this->actingAs($user)
            ->get(route('account.dashboard'))
            ->assertOk()
            ->assertSee('Designer<span class="text-coral-500">.</span>', escape: false)
            ->assertSee('Grace Hopper')
            ->assertSee('Your designs')
            ->assertSee('Neon Bloom')
            ->assertSee('Orders', escape: false); // designer sidebar link
    }

    public function test_account_dashboard_renders_printer_view_for_printer(): void
    {
        $user = User::factory()->printerProvider()->create(['name' => 'Patti Plates']);
        $profile = PrinterProviderProfile::factory()->for($user)->create(['company_name' => 'Printy Co']);
        ProductTemplate::factory()->for($profile, 'printerProvider')->create(['type' => 'mug']);

        $this->actingAs($user)
            ->get(route('account.dashboard'))
            ->assertOk()
            ->assertSee('Printer<span class="text-coral-500">.</span>', escape: false)
            ->assertSee('Printy Co')
            ->assertSee('Your templates')
            ->assertSee('mug')
            ->assertSee('Recent order items');
    }

    public function test_account_dashboard_renders_customer_view_for_customer(): void
    {
        $user = User::factory()->customer()->create(['name' => 'Pat Customer']);

        $this->actingAs($user)
            ->get(route('account.dashboard'))
            ->assertOk()
            ->assertSee('Account<span class="text-coral-500">.</span>', escape: false)
            ->assertSee('Pat Customer')
            ->assertSee('Profile')
            ->assertSee('Change password')
            ->assertDontSee('Your designs')
            ->assertDontSee('Your templates');
    }

    public function test_account_dashboard_renders_printer_view_lists_printer_order_items(): void
    {
        $user = User::factory()->printerProvider()->create();
        $profile = PrinterProviderProfile::factory()->for($user)->create();

        OrderItem::factory()
            ->for(Order::factory())
            ->create([
                'printer_provider_id' => $profile->id,
                'status' => 'pending',
                'quantity' => 3,
                'unit_price' => 10.00,
            ]);

        $this->actingAs($user)
            ->get(route('account.dashboard'))
            ->assertOk()
            ->assertSee('pending')
            ->assertSee('30.00'); // 3 * 10.00
    }

    // ------------------------------------------------------------------
    // Profile update
    // ------------------------------------------------------------------

    public function test_update_profile_with_valid_data_succeeds(): void
    {
        $user = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
        ]);

        $this->actingAs($user)
            ->patch(route('account.profile.update'), [
                'name' => 'New Name',
                'email' => 'new@example.com',
                'phone' => '+15551234567',
            ])
            ->assertRedirect(route('account.dashboard'))
            ->assertSessionHas('status', 'Profile updated.');

        $user->refresh();
        $this->assertSame('New Name', $user->name);
        $this->assertSame('new@example.com', $user->email);
        $this->assertSame('+15551234567', $user->phone);
    }

    public function test_update_profile_with_duplicate_email_fails(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);
        $user = User::factory()->create(['email' => 'mine@example.com']);

        $this->actingAs($user)
            ->from(route('account.dashboard'))
            ->patch(route('account.profile.update'), [
                'name' => 'Mine',
                'email' => 'taken@example.com',
                'phone' => null,
            ])
            ->assertRedirect(route('account.dashboard'))
            ->assertSessionHasErrors('email');

        $user->refresh();
        $this->assertSame('mine@example.com', $user->email);
    }

    public function test_update_profile_keeps_email_when_unchanged(): void
    {
        $user = User::factory()->create(['email' => 'same@example.com']);

        $this->actingAs($user)
            ->patch(route('account.profile.update'), [
                'name' => 'Updated',
                'email' => 'same@example.com',
                'phone' => null,
            ])
            ->assertRedirect(route('account.dashboard'))
            ->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame('same@example.com', $user->email);
        $this->assertSame('Updated', $user->name);
    }

    // ------------------------------------------------------------------
    // Password update
    // ------------------------------------------------------------------

    public function test_update_password_with_correct_current_succeeds(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('OldPass1!'),
        ]);

        $this->actingAs($user)
            ->patch(route('account.password.update'), [
                'current_password' => 'OldPass1!',
                'password' => 'NewPass1!',
                'password_confirmation' => 'NewPass1!',
            ])
            ->assertRedirect(route('account.dashboard'))
            ->assertSessionHas('status', 'Password updated.');

        $user->refresh();
        $this->assertTrue(Hash::check('NewPass1!', $user->password));
        $this->assertFalse(Hash::check('OldPass1!', $user->password));
    }

    public function test_update_password_with_wrong_current_fails(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('OldPass1!'),
        ]);

        $this->actingAs($user)
            ->from(route('account.dashboard'))
            ->patch(route('account.password.update'), [
                'current_password' => 'WrongPass1!',
                'password' => 'NewPass1!',
                'password_confirmation' => 'NewPass1!',
            ])
            ->assertRedirect(route('account.dashboard'))
            ->assertSessionHasErrors('current_password');

        $user->refresh();
        $this->assertTrue(Hash::check('OldPass1!', $user->password));
    }

    // ------------------------------------------------------------------
    // Addresses
    // ------------------------------------------------------------------

    public function test_list_addresses_for_authenticated_user(): void
    {
        $user = User::factory()->create();
        Address::factory()->count(2)->for($user)->create();
        // Other user's address — must NOT show up.
        Address::factory()->create();

        $this->actingAs($user)
            ->get(route('account.addresses.index'))
            ->assertOk()
            ->assertSee('Addresses')
            ->assertSee('+ New address');
    }

    public function test_create_address_redirects_to_index_with_flash(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('account.addresses.store'), [
                'line1' => '12 Main St',
                'city' => 'Paris',
                'country' => 'France',
                'phone' => '+33123456789',
            ])
            ->assertRedirect(route('account.addresses.index'))
            ->assertSessionHas('status', 'Address added.');

        $this->assertDatabaseHas('addresses', [
            'user_id' => $user->id,
            'line1' => '12 Main St',
            'city' => 'Paris',
            'country' => 'France',
        ]);
    }

    public function test_update_address_for_owner_succeeds(): void
    {
        $user = User::factory()->create();
        $address = Address::factory()->for($user)->create(['city' => 'Lyon']);

        $this->actingAs($user)
            ->patch(route('account.addresses.update', $address), [
                'line1' => $address->line1,
                'city' => 'Marseille',
                'country' => $address->country,
                'phone' => $address->phone,
            ])
            ->assertRedirect(route('account.addresses.index'))
            ->assertSessionHas('status', 'Address updated.');

        $address->refresh();
        $this->assertSame('Marseille', $address->city);
    }

    public function test_cannot_edit_another_users_address(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $address = Address::factory()->for($owner)->create();

        $this->actingAs($other)
            ->get(route('account.addresses.edit', $address))
            ->assertForbidden();

        $this->actingAs($other)
            ->patch(route('account.addresses.update', $address), [
                'line1' => 'Hacked',
                'city' => 'X',
                'country' => 'Y',
                'phone' => null,
            ])
            ->assertForbidden();

        $address->refresh();
        $this->assertNotSame('Hacked', $address->line1);
    }

    public function test_delete_address_redirects_with_flash(): void
    {
        $user = User::factory()->create();
        $address = Address::factory()->for($user)->create();

        $this->actingAs($user)
            ->delete(route('account.addresses.destroy', $address))
            ->assertRedirect(route('account.addresses.index'))
            ->assertSessionHas('status', 'Address removed.');

        $this->assertDatabaseMissing('addresses', ['id' => $address->id]);
    }

    public function test_delete_last_address_still_shows_flash_on_empty_index(): void
    {
        // Regression: the status flash was previously nested inside the
        // @else (non-empty) branch, so deleting the final address rendered
        // an empty page without the success message.
        $user = User::factory()->create();
        $address = Address::factory()->for($user)->create();

        $this->actingAs($user)
            ->delete(route('account.addresses.destroy', $address))
            ->assertRedirect(route('account.addresses.index'))
            ->assertSessionHas('status', 'Address removed.');

        $this->actingAs($user)
            ->get(route('account.addresses.index'))
            ->assertOk()
            ->assertSee('Address removed.', escape: false);
    }

    public function test_delete_address_refused_if_used_by_order(): void
    {
        $user = User::factory()->create();
        $address = Address::factory()->for($user)->create();
        Order::factory()
            ->forCustomer($user)
            ->withShippingAddress($address)
            ->pending()
            ->create();

        $this->actingAs($user)
            ->from(route('account.addresses.index'))
            ->delete(route('account.addresses.destroy', $address))
            ->assertRedirect(route('account.addresses.index'))
            ->assertSessionHasErrors('address');

        $this->assertDatabaseHas('addresses', ['id' => $address->id]);
    }

    public function test_cannot_delete_another_users_address(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $address = Address::factory()->for($owner)->create();

        $this->actingAs($other)
            ->delete(route('account.addresses.destroy', $address))
            ->assertForbidden();

        $this->assertDatabaseHas('addresses', ['id' => $address->id]);
    }

    // ------------------------------------------------------------------
    // Orders
    // ------------------------------------------------------------------

    public function test_list_orders_paginated(): void
    {
        $user = User::factory()->create();
        Order::factory()->count(3)->forCustomer($user)->pending()->create();

        $response = $this->actingAs($user)
            ->get(route('account.orders'))
            ->assertOk();

        $response->assertSee('Orders');
        $response->assertSee('pending');
    }

    public function test_list_orders_does_not_show_other_users_orders(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        Order::factory()->forCustomer($other)->pending()->create();

        $response = $this->actingAs($user)
            ->get(route('account.orders'))
            ->assertOk();

        // No order rows in the body for this user.
        $response->assertDontSee('pending');
    }

    // ------------------------------------------------------------------
    // Notifications
    // ------------------------------------------------------------------

    public function test_list_notifications(): void
    {
        $user = User::factory()->create();
        Notification::factory()->count(2)->create([
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('account.notifications'))
            ->assertOk()
            ->assertSee('Notifications');
    }

    public function test_mark_notification_as_read(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->unread()->create([
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
        ]);

        $this->assertNull($notification->read_at);

        $this->actingAs($user)
            ->patch(route('account.notifications.read', $notification->id))
            ->assertRedirect();

        $notification->refresh();
        $this->assertNotNull($notification->read_at);
    }

    public function test_delete_notification(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->create([
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->delete(route('account.notifications.destroy', $notification->id))
            ->assertRedirect()
            ->assertSessionHas('status', 'Notification removed.');

        $this->assertDatabaseMissing('notifications', ['id' => $notification->id]);
    }

    public function test_cannot_mark_another_users_notification_as_read(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $notification = Notification::factory()->unread()->create([
            'notifiable_type' => User::class,
            'notifiable_id' => $owner->id,
        ]);

        $this->actingAs($other)
            ->patch(route('account.notifications.read', $notification->id))
            ->assertNotFound();

        $notification->refresh();
        $this->assertNull($notification->read_at);
    }
}
