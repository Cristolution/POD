<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PrinterProviderProfile;
use App\Models\ProductTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrinterDashboardTest extends TestCase
{
    use RefreshDatabase;

    // ------------------------------------------------------------------
    // Public profile
    // ------------------------------------------------------------------

    public function test_public_printer_profile_is_reachable_and_shows_company_and_templates(): void
    {
        $printer = PrinterProviderProfile::factory()
            ->for(User::factory()->printerProvider()->create())
            ->create(['company_name' => 'Printy Co']);

        ProductTemplate::factory()
            ->for($printer, 'printerProvider')
            ->create(['name' => 'Classic Mug', 'type' => 'mug', 'base_cost' => 8.50]);

        $this->get(route('printer.show', $printer))
            ->assertOk()
            ->assertSee('Printy Co')
            ->assertSee('Classic Mug')
            ->assertSee('Product templates');
    }

    public function test_public_printer_profile_404_for_unknown(): void
    {
        $this->get('/printers/00000000-0000-0000-0000-000000000000')
            ->assertNotFound();
    }

    public function test_public_printer_profile_lists_templates(): void
    {
        $printer = PrinterProviderProfile::factory()->create(['company_name' => 'Printy Co']);

        ProductTemplate::factory()->count(3)->for($printer, 'printerProvider')->create();

        // Template owned by another printer must not appear.
        ProductTemplate::factory()->create(['name' => 'Not Mine']);

        $this->get(route('printer.show', $printer))
            ->assertOk()
            ->assertSee('Product templates')
            ->assertDontSee('Not Mine');
    }

    // ------------------------------------------------------------------
    // Printer dashboard (auth + role:printer_provider)
    // ------------------------------------------------------------------

    public function test_dashboard_redirects_anonymous_to_login(): void
    {
        $this->get(route('printer.dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_dashboard_returns_403_for_customer(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->get(route('printer.dashboard'))
            ->assertForbidden();
    }

    public function test_dashboard_returns_403_for_designer(): void
    {
        $designer = User::factory()->designer()->create();

        $this->actingAs($designer)
            ->get(route('printer.dashboard'))
            ->assertForbidden();
    }

    public function test_dashboard_returns_200_for_printer(): void
    {
        $user = User::factory()->printerProvider()->create();
        $profile = PrinterProviderProfile::factory()
            ->for($user)
            ->create(['company_name' => 'Printy Co']);

        ProductTemplate::factory()->for($profile, 'printerProvider')->count(2)->create();

        $this->actingAs($user)
            ->get(route('printer.dashboard'))
            ->assertOk()
            ->assertSee('Printer')
            ->assertSee('Printy Co')
            ->assertSee('Edit profile')
            ->assertSee('Your templates')
            ->assertSee('Recent order items');
    }

    public function test_dashboard_lists_order_items_assigned_to_printer(): void
    {
        $user = User::factory()->printerProvider()->create();
        $profile = PrinterProviderProfile::factory()->for($user)->create();

        // One order item belonging to this printer.
        OrderItem::factory()
            ->for(Order::factory())
            ->create([
                'printer_provider_id' => $profile->id,
                'status' => 'pending',
                'quantity' => 2,
                'unit_price' => 15.00,
            ]);

        $this->actingAs($user)
            ->get(route('printer.dashboard'))
            ->assertOk()
            ->assertSee('pending')
            ->assertSee('30.00'); // 2 * 15.00
    }

    // ------------------------------------------------------------------
    // Printer edit
    // ------------------------------------------------------------------

    public function test_edit_page_redirects_anonymous_to_login(): void
    {
        $this->get(route('printer.edit'))->assertRedirect(route('login'));
    }

    public function test_edit_page_renders_form_for_printer(): void
    {
        $user = User::factory()->printerProvider()->create();
        PrinterProviderProfile::factory()->for($user)->create(['company_name' => 'Old Name']);

        $this->actingAs($user)
            ->get(route('printer.edit'))
            ->assertOk()
            ->assertSee('Edit profile')
            ->assertSee('Old Name');
    }

    public function test_update_profile_with_valid_company_name_persists(): void
    {
        $user = User::factory()->printerProvider()->create();
        $profile = PrinterProviderProfile::factory()->for($user)->create(['company_name' => 'Old']);

        $this->actingAs($user)
            ->patch(route('printer.update'), [
                'company_name' => 'New Print Studio',
            ])
            ->assertRedirect(route('printer.edit'))
            ->assertSessionHas('status', 'Profile updated.');

        $profile->refresh();
        $this->assertSame('New Print Studio', $profile->company_name);
    }

    public function test_update_profile_with_missing_company_name_fails(): void
    {
        $user = User::factory()->printerProvider()->create();
        $profile = PrinterProviderProfile::factory()->for($user)->create(['company_name' => 'Original']);

        $this->actingAs($user)
            ->from(route('printer.edit'))
            ->patch(route('printer.update'), [
                'company_name' => '',
            ])
            ->assertRedirect(route('printer.edit'))
            ->assertSessionHasErrors('company_name');

        $profile->refresh();
        $this->assertSame('Original', $profile->company_name);
    }
}
