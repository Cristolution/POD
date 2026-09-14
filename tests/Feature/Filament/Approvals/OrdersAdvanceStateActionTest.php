<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The new `advanceState` row action on OrdersTable walks the same state
 * machine the Order view page uses (MarkOrder*Action), giving admins a
 * one-click alternative without opening the record.
 */
class OrdersAdvanceStateActionTest extends TestCase
{
    use RefreshDatabase;

    public static function advanceCases(): array
    {
        return [
            'pending → paid' => ['pending', 'paid'],
            'paid → processing' => ['paid', 'processing'],
            'processing → shipped' => ['processing', 'shipped'],
            'shipped → delivered' => ['shipped', 'delivered'],
        ];
    }

    #[DataProvider('advanceCases')]
    public function test_admin_can_advance_order_via_row_action(string $from, string $expected): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $order = Order::factory()->create(['status' => $from]);

        Livewire::actingAs($admin)
            ->test(ListOrders::class)
            ->callTableAction('advanceState', $order);

        $this->assertSame($expected, $order->fresh()->status);
    }

    public function test_advance_action_is_hidden_for_terminal_states(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $delivered = Order::factory()->create(['status' => 'delivered']);
        $cancelled = Order::factory()->create(['status' => 'cancelled']);

        $component = Livewire::actingAs($admin)->test(ListOrders::class);

        $component->assertTableActionHidden('advanceState', $delivered);
        $component->assertTableActionHidden('advanceState', $cancelled);
    }

    public function test_advance_action_is_visible_for_open_states(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $pending = Order::factory()->create(['status' => 'pending']);
        $paid = Order::factory()->create(['status' => 'paid']);
        $processing = Order::factory()->create(['status' => 'processing']);
        $shipped = Order::factory()->create(['status' => 'shipped']);

        $component = Livewire::actingAs($admin)->test(ListOrders::class);

        $component->assertTableActionVisible('advanceState', $pending);
        $component->assertTableActionVisible('advanceState', $paid);
        $component->assertTableActionVisible('advanceState', $processing);
        $component->assertTableActionVisible('advanceState', $shipped);
    }
}
