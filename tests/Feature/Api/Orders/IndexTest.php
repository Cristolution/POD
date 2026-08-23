<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Orders;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_orders(): void
    {
        Order::factory()->count(3)->pending()->create();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/orders')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_customer_cannot_list_all_orders(): void
    {
        Order::factory()->count(2)->create();
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer, 'sanctum')
            ->getJson('/api/orders')
            ->assertForbidden();
    }

    public function test_status_filter_works(): void
    {
        Order::factory()->count(2)->pending()->create();
        Order::factory()->count(3)->delivered()->create();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/orders?status=pending')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }
}
