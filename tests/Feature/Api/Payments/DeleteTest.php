<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Payments;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_soft_delete_payment(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->pending()->forCustomer($customer)->create();
        $payment = Payment::factory()->create(['order_id' => $order->id]);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/admin/payments/{$payment->id}")
            ->assertNoContent();

        $this->assertSoftDeleted('payments', ['id' => $payment->id]);
    }
}
