<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_admin_can_view_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/dashboard')
            ->assertOk();

        $expectedKeys = [
            'total_customers',
            'total_designers',
            'total_printers',
            'total_published_designs',
            'total_active_templates',
            'orders_today',
            'orders_this_month',
            'revenue_today',
            'revenue_this_month',
            'pending_payments',
            'pending_order_items',
            'in_flight_shipments',
            'abandoned_carts_24h',
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $response->json(), "Dashboard response missing key '{$key}'.");
        }
    }

    public function test_customer_cannot_view_dashboard(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer, 'sanctum')
            ->getJson('/api/admin/dashboard')
            ->assertForbidden();
    }
}
