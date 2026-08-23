<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Payments;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\PaymentConfirmedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ConfirmTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_confirm_pending_payment(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->pending()->forCustomer($customer)->create();
        $payment = Payment::factory()->cashOnDelivery()->create([
            'order_id' => $order->id,
        ]);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/payments/{$payment->id}/confirm", ['status' => 'confirmed'])
            ->assertOk()
            ->assertJsonPath('data.status', 'confirmed')
            ->assertJsonPath('data.confirmed_by_admin_id', $admin->id);

        $payment->refresh();
        $this->assertSame('confirmed', $payment->status);
        $this->assertSame($admin->id, $payment->confirmed_by_admin_id);
        $this->assertNotNull($payment->confirmed_at);

        Notification::assertSentTo($customer, PaymentConfirmedNotification::class);
    }

    public function test_customer_cannot_confirm(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->pending()->forCustomer($customer)->create();
        $payment = Payment::factory()->cashOnDelivery()->create([
            'order_id' => $order->id,
        ]);

        $this->actingAs($customer, 'sanctum')
            ->patchJson("/api/payments/{$payment->id}/confirm", ['status' => 'confirmed'])
            ->assertForbidden();
    }

    public function test_double_confirm_returns_409(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->pending()->forCustomer($customer)->create();
        $payment = Payment::factory()->confirmed($admin)->create([
            'order_id' => $order->id,
        ]);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/payments/{$payment->id}/confirm", ['status' => 'confirmed'])
            ->assertStatus(409);
    }
}
