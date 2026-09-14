<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Approvals;

use App\Filament\Pages\Approvals\PendingPaymentsPage;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PendingPaymentsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_pending_payments_queue(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->forCustomer($customer)->create(['status' => 'pending']);
        Payment::factory()->create([
            'order_id' => $order->id,
            'status' => 'pending',
        ]);

        Livewire::actingAs($admin)
            ->test(PendingPaymentsPage::class)
            ->assertSuccessful()
            ->assertSee($order->id)
            ->assertSee($customer->name);
    }

    public function test_confirmed_and_rejected_payments_do_not_appear(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);

        $pendingOrder = Order::factory()->forCustomer($customer)->create();
        Payment::factory()->create(['order_id' => $pendingOrder->id, 'status' => 'pending']);

        $confirmedOrder = Order::factory()->forCustomer($customer)->create();
        Payment::factory()->confirmed($admin)->create(['order_id' => $confirmedOrder->id]);

        $rejectedOrder = Order::factory()->forCustomer($customer)->create();
        Payment::factory()->rejected()->create(['order_id' => $rejectedOrder->id]);

        $component = Livewire::actingAs($admin)->test(PendingPaymentsPage::class);

        $this->assertCount(1, $component->get('payments'));
        $this->assertSame('pending', $component->get('payments')->first()->status);
    }

    public function test_admin_can_confirm_pending_payment(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->forCustomer($customer)->create();
        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'status' => 'pending',
        ]);

        Livewire::actingAs($admin)
            ->test(PendingPaymentsPage::class)
            ->call('confirmPayment', $payment->id);

        $payment->refresh();
        $this->assertSame('confirmed', $payment->status);
        $this->assertSame($admin->id, $payment->confirmed_by_admin_id);
        $this->assertNotNull($payment->confirmed_at);
    }

    public function test_admin_can_reject_pending_payment(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->forCustomer($customer)->create();
        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'status' => 'pending',
        ]);

        Livewire::actingAs($admin)
            ->test(PendingPaymentsPage::class)
            ->call('rejectPayment', $payment->id);

        $payment->refresh();
        $this->assertSame('rejected', $payment->status);
        $this->assertSame($admin->id, $payment->confirmed_by_admin_id);
    }

    public function test_confirming_already_confirmed_payment_is_a_noop(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->forCustomer($customer)->create();
        $payment = Payment::factory()->confirmed($admin)->create(['order_id' => $order->id]);

        Livewire::actingAs($admin)
            ->test(PendingPaymentsPage::class)
            ->call('confirmPayment', $payment->id)
            ->assertHasNoErrors();

        $payment->refresh();
        $this->assertSame('confirmed', $payment->status);
    }

    public function test_queue_refreshes_after_action(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->forCustomer($customer)->create();
        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'status' => 'pending',
        ]);

        $component = Livewire::actingAs($admin)->test(PendingPaymentsPage::class);
        $this->assertCount(1, $component->get('payments'));

        $component->call('confirmPayment', $payment->id);

        $this->assertCount(0, $component->get('payments'));
    }

    public function test_non_admin_cannot_reach_pending_payments(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer)
            ->get('/admin/approvals-payments')
            ->assertForbidden();
    }
}
