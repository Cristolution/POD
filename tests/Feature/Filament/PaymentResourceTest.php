<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Resources\Payments\Pages\CreatePayment;
use App\Filament\Resources\Payments\Pages\ListPayments;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PaymentResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_payments(): void
    {
        Payment::factory()->count(3)->create();
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(ListPayments::class)
            ->assertSuccessful();
    }

    public function test_admin_can_create_payment(): void
    {
        $order = Order::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(CreatePayment::class)
            ->fillForm([
                'order_id' => (string) $order->id,
                'method' => 'bank_transfer',
                'status' => 'pending',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'method' => 'bank_transfer',
            'status' => 'pending',
        ]);
    }
}
