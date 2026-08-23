<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Payments;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_list_own_payments(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->pending()->forCustomer($customer)->create();

        Payment::factory()->count(2)->create(['order_id' => $order->id]);

        $this->actingAs($customer, 'sanctum')
            ->getJson('/api/me/payments')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_user_cannot_see_other_users_payments(): void
    {
        $owner = User::factory()->create(['role' => 'customer']);
        $ownerOrder = Order::factory()->pending()->forCustomer($owner)->create();
        Payment::factory()->count(3)->create(['order_id' => $ownerOrder->id]);

        $stranger = User::factory()->create(['role' => 'customer']);

        $this->actingAs($stranger, 'sanctum')
            ->getJson('/api/me/payments')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }
}
