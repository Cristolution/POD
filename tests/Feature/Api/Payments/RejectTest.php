<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Payments;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RejectTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_reject_pending_payment(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->pending()->forCustomer($customer)->create();
        $payment = Payment::factory()->cashOnDelivery()->create([
            'order_id' => $order->id,
        ]);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/payments/{$payment->id}/reject", ['status' => 'rejected'])
            ->assertOk()
            ->assertJsonPath('data.status', 'rejected');

        $this->assertSame('rejected', $payment->fresh()->status);
        $this->assertSame($admin->id, $payment->fresh()->confirmed_by_admin_id);
    }

    public function test_cannot_reject_confirmed_payment(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->pending()->forCustomer($customer)->create();
        $payment = Payment::factory()->confirmed($admin)->create([
            'order_id' => $order->id,
        ]);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/payments/{$payment->id}/reject", ['status' => 'rejected'])
            ->assertStatus(409);
    }
}
