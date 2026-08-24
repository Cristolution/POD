<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_anonymous_user_is_redirected_to_admin_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_customer_role_user_is_forbidden(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $this->actingAs($customer)->get('/admin')->assertForbidden();
    }

    public function test_designer_role_user_is_forbidden(): void
    {
        $designer = User::factory()->create(['role' => 'designer']);
        $this->actingAs($designer)->get('/admin')->assertForbidden();
    }

    public function test_printer_provider_role_user_is_forbidden(): void
    {
        $printer = User::factory()->create(['role' => 'printer_provider']);
        $this->actingAs($printer)->get('/admin')->assertForbidden();
    }

    public function test_admin_role_user_can_access_admin_panel(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get('/admin')->assertOk();
    }

    public function test_customer_403_response_is_html_not_json(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $response = $this->actingAs($customer)->get('/admin');
        $response->assertForbidden();
        // The response body should NOT contain a JSON `"message":` key
        $this->assertStringNotContainsString('"message":', $response->getContent());
    }
}
