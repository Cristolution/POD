<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Pages\KpiDashboard;
use App\Filament\Widgets\AbandonedCartStat;
use App\Filament\Widgets\PendingOrdersStat;
use App\Filament\Widgets\RecentOrdersTable;
use App\Filament\Widgets\RevenueChart;
use App\Filament\Widgets\RevenueTodayStat;
use App\Filament\Widgets\TotalCustomersStat;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class KpiDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_loads_for_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/admin')->assertOk();
    }

    public function test_dashboard_is_routable_as_livewire_component(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(KpiDashboard::class)
            ->assertSuccessful();
    }

    public function test_dashboard_lists_all_six_widgets_in_header_and_footer(): void
    {
        $page = new KpiDashboard;
        $reflection = new \ReflectionMethod($page, 'getHeaderWidgets');
        $reflection->setAccessible(true);

        $this->assertSame(
            [
                TotalCustomersStat::class,
                RevenueTodayStat::class,
                PendingOrdersStat::class,
                AbandonedCartStat::class,
            ],
            $reflection->invoke($page),
        );

        $reflection = new \ReflectionMethod($page, 'getFooterWidgets');
        $reflection->setAccessible(true);

        $this->assertSame(
            [
                RevenueChart::class,
                RecentOrdersTable::class,
            ],
            $reflection->invoke($page),
        );
    }

    public function test_total_customers_stat_renders_with_correct_counts(): void
    {
        User::factory()->count(7)->create(['role' => 'customer']);
        User::factory()->count(2)->create(['role' => 'designer']);
        User::factory()->count(4)->create(['role' => 'printer_provider']);

        Livewire::test(TotalCustomersStat::class)
            ->assertSee('7')   // Customers count
            ->assertSee('2')   // Designers count
            ->assertSee('4');  // Printers count
    }

    public function test_pending_orders_stat_counts_pending_and_paid_statuses(): void
    {
        Order::factory()->count(3)->create(['status' => 'pending']);
        Order::factory()->count(2)->create(['status' => 'paid']);
        Order::factory()->count(5)->create(['status' => 'shipped']);
        Order::factory()->count(1)->create(['status' => 'cancelled']);

        Livewire::test(PendingOrdersStat::class)
            ->assertSee('5')   // pending + paid
            ->assertSee('Pending orders');
    }

    public function test_revenue_today_stat_renders_confirmed_payment_only(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);

        $confirmed = Order::factory()->forCustomer($customer)->create([
            'status' => 'paid',
            'total_amount' => 42.50,
        ]);
        Payment::factory()->confirmed($admin)->create([
            'order_id' => $confirmed->id,
            'confirmed_at' => now(),
        ]);

        $pending = Order::factory()->forCustomer($customer)->create([
            'status' => 'pending',
            'total_amount' => 999.99,
        ]);
        Payment::factory()->create([
            'order_id' => $pending->id,
            'status' => 'pending',
            'confirmed_at' => null,
        ]);

        Livewire::test(RevenueTodayStat::class)
            ->assertSee('$42.50')
            ->assertDontSee('$999.99');
    }

    public function test_abandoned_cart_stat_counts_only_items_older_than_24h(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        CartItem::factory()->count(2)->for($customer, 'user')->create([
            'created_at' => now()->subHours(48),
            'updated_at' => now()->subHours(48),
        ]);
        CartItem::factory()->count(3)->for($customer, 'user')->create([
            'created_at' => now()->subHours(2),
            'updated_at' => now()->subHours(2),
        ]);

        Livewire::test(AbandonedCartStat::class)
            ->assertSee('Abandoned carts')
            ->assertSee('2');
    }

    public function test_revenue_chart_renders_with_confirmed_payment_data(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);

        $order = Order::factory()->forCustomer($customer)->create([
            'status' => 'paid',
            'total_amount' => 100.00,
        ]);
        Payment::factory()->confirmed($admin)->create([
            'order_id' => $order->id,
            'confirmed_at' => now(),
        ]);

        Livewire::test(RevenueChart::class)->assertSuccessful();
    }

    public function test_recent_orders_table_renders_last_ten_orders(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        Order::factory()->count(15)->forCustomer($customer)->create();

        Livewire::test(RecentOrdersTable::class)->assertSuccessful();
    }
}
