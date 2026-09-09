<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Pages\KpiDashboard;
use App\Filament\Widgets\ActionQueueStat;
use App\Filament\Widgets\RecentOrdersTable;
use App\Filament\Widgets\RevenueChart;
use App\Filament\Widgets\SalesTodayStat;
use App\Filament\Widgets\TotalCustomersStat;
use App\Models\CartItem;
use App\Models\Design;
use App\Models\DesignerProfile;
use App\Models\DesignProductMapping;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\ProductVariant;
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

        $this->actingAs($admin)->get('/admin')->assertOk()->assertSee('POD Admin');
    }

    public function test_dashboard_is_forbidden_for_non_admin(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer)->get('/admin')->assertForbidden();
    }

    public function test_dashboard_is_routable_as_livewire_component(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(KpiDashboard::class)
            ->assertSuccessful();
    }

    public function test_dashboard_lists_five_widgets_across_header_and_footer(): void
    {
        $page = new KpiDashboard;
        $reflection = new \ReflectionMethod($page, 'getHeaderWidgets');
        $reflection->setAccessible(true);

        $this->assertSame(
            [
                TotalCustomersStat::class,
                SalesTodayStat::class,
                ActionQueueStat::class,
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

    public function test_dashboard_content_slot_is_empty_so_widgets_appear_only_once(): void
    {
        // Filament v4's `Dashboard::content()` renders `getWidgets()` between the
        // header and footer widget grids. The parent default is `Filament::getWidgets()`
        // — i.e. every panel-registered widget — which would duplicate the header
        // and footer widgets AND leak the `Reports/*Chart` widgets onto the admin
        // home. The override must keep the content slot empty.
        $page = new KpiDashboard;

        $this->assertSame([], $page->getWidgets());
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

    public function test_sales_today_stat_renders_confirmed_payment_revenue(): void
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

        Livewire::test(SalesTodayStat::class)
            ->assertSee('$42.50')
            ->assertDontSee('$999.99');
    }

    public function test_sales_today_stat_renders_average_order_value(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);

        // 2 confirmed orders today totalling $200 → AOV $100.
        foreach ([100.00, 100.00] as $amount) {
            $order = Order::factory()->forCustomer($customer)->create([
                'status' => 'paid',
                'total_amount' => $amount,
            ]);
            Payment::factory()->confirmed($admin)->create([
                'order_id' => $order->id,
                'confirmed_at' => now(),
            ]);
        }

        Livewire::test(SalesTodayStat::class)
            ->assertSee('Avg order value')
            ->assertSee('$100.00');
    }

    public function test_sales_today_stat_renders_refund_rate(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        // 9 orders last 7 days, 3 cancelled → 33.3% refund rate
        Order::factory()->count(6)->forCustomer($customer)->create(['status' => 'paid']);
        Order::factory()->count(3)->forCustomer($customer)->create(['status' => 'cancelled']);

        Livewire::test(SalesTodayStat::class)
            ->assertSee('Refund rate')
            ->assertSee('33.3%');
    }

    public function test_action_queue_stat_counts_pending_orders_and_payments(): void
    {
        Order::factory()->count(3)->create(['status' => 'pending']);
        Order::factory()->count(2)->create(['status' => 'paid']);
        Order::factory()->count(5)->create(['status' => 'shipped']);
        Order::factory()->count(1)->create(['status' => 'cancelled']);

        Payment::factory()->count(4)->create(['status' => 'pending']);

        Livewire::test(ActionQueueStat::class)
            ->assertSee('5')        // pending + paid orders
            ->assertSee('Pending orders')
            ->assertSee('4')        // pending payments
            ->assertSee('Pending payments');
    }

    public function test_action_queue_stat_counts_in_flight_shipments(): void
    {
        OrderItem::factory()->count(2)->create(['status' => 'printing']);
        OrderItem::factory()->count(3)->create(['status' => 'printed']);
        OrderItem::factory()->count(1)->create(['status' => 'handed_off']);
        OrderItem::factory()->count(1)->create(['status' => 'cancelled']);

        Livewire::test(ActionQueueStat::class)
            ->assertSee('In-flight shipments')
            ->assertSee('6'); // printing + printed + handed_off
    }

    public function test_action_queue_stat_counts_only_items_older_than_24h(): void
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

        Livewire::test(ActionQueueStat::class)
            ->assertSee('Abandoned carts')
            ->assertSee('2');
    }

    public function test_action_queue_stat_estimates_revenue_at_risk(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $designer = User::factory()->create(['role' => 'designer']);
        $designerProfile = DesignerProfile::factory()->create(['user_id' => $designer->id]);
        $design = Design::factory()->create([
            'designer_id' => $designerProfile->id,
            'status' => 'published',
        ]);

        $mapping = DesignProductMapping::factory()->create([
            'design_id' => $design->id,
            'final_price' => 25.00,
        ]);
        $variant = ProductVariant::factory()->create([
            'product_template_id' => $mapping->product_template_id,
            'price_delta' => 5.00,
        ]);

        // 1 abandoned item × quantity 2 × (25 + 5) = $60 at risk. We can't
        // create a second row with the same (user, mapping, variant) triple
        // because of the cart_items_unique_line constraint.
        CartItem::factory()->create([
            'user_id' => $customer->id,
            'design_product_mapping_id' => $mapping->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
            'created_at' => now()->subHours(48),
            'updated_at' => now()->subHours(48),
        ]);

        Livewire::test(ActionQueueStat::class)
            ->assertSee('Abandoned carts')
            ->assertSee('$60');
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
