<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Reports;

use App\Filament\Pages\Reports\BaseReportPage;
use App\Filament\Pages\Reports\CustomerLtvReport;
use App\Filament\Pages\Reports\OrderStatusDistributionReport;
use App\Filament\Pages\Reports\OverviewReport;
use App\Filament\Pages\Reports\RefundRateReport;
use App\Filament\Pages\Reports\RevenueByDayReport;
use App\Filament\Pages\Reports\RevenueByDesignerReport;
use App\Filament\Pages\Reports\RevenueByPrinterReport;
use App\Filament\Pages\Reports\TopDesignsReport;
use App\Filament\Widgets\Reports\CustomerLtvChart;
use App\Filament\Widgets\Reports\OrderStatusDistributionChart;
use App\Filament\Widgets\Reports\RefundRateChart;
use App\Filament\Widgets\Reports\RevenueByDayChart;
use App\Filament\Widgets\Reports\RevenueByDesignerChart;
use App\Filament\Widgets\Reports\RevenueByPrinterChart;
use App\Filament\Widgets\Reports\TopDesignsChart;
use App\Models\Design;
use App\Models\DesignerProfile;
use App\Models\DesignProductMapping;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PrinterProviderProfile;
use App\Models\User;
use Filament\Widgets\ChartWidget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Charts paired with admin Report pages — smoke, empty-state, populated
 * fixtures, and the BaseReportPage → chartWidgets() wiring contract.
 */
class ReportChartsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Every chart page must declare its widget(s) via {@see BaseReportPage::chartWidgets()}.
     * The Overview report intentionally has no chart (metric/value pairs are
     * already a tidy KPI summary, not a chartable time/category series).
     *
     * @return array<string, array{0: class-string<BaseReportPage>, 1: array<int, class-string>}>
     */
    public static function chartedPages(): array
    {
        return [
            'revenue-by-day' => [RevenueByDayReport::class, [RevenueByDayChart::class]],
            'revenue-by-designer' => [RevenueByDesignerReport::class, [RevenueByDesignerChart::class]],
            'revenue-by-printer' => [RevenueByPrinterReport::class, [RevenueByPrinterChart::class]],
            'top-designs' => [TopDesignsReport::class, [TopDesignsChart::class]],
            'customer-ltv' => [CustomerLtvReport::class, [CustomerLtvChart::class]],
            'order-status-distribution' => [OrderStatusDistributionReport::class, [OrderStatusDistributionChart::class]],
            'refund-rate' => [RefundRateReport::class, [RefundRateChart::class]],
        ];
    }

    /**
     * Flat list of every report-chart class — used to fan-out the smoke
     * and "applies brutalist styling" tests via data provider.
     *
     * @return array<string, array<int, class-string>>
     */
    public static function chartWidgets(): array
    {
        $pairs = [];
        foreach (self::chartedPages() as $slug => [$page, $widgets]) {
            foreach ($widgets as $widget) {
                $pairs["{$slug} → ".class_basename($widget)] = [$widget];
            }
        }

        return $pairs;
    }

    /**
     * `ChartWidget::getData()` is `protected`, so reflection is the only
     * way to call it from outside the widget without exposing a public
     * hook purely for tests.
     *
     * @return array<string, mixed>
     */
    private function invokeGetData(ChartWidget $widget): array
    {
        $reflection = new \ReflectionMethod($widget, 'getData');
        $reflection->setAccessible(true);

        return $reflection->invoke($widget);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Chart widget smoke tests — every widget must at least render with the
    // brutalist styling and an empty dataset.
    // ─────────────────────────────────────────────────────────────────────────

    #[DataProvider('chartWidgets')]
    public function test_chart_widget_renders_with_empty_dataset(string $widgetClass): void
    {
        $data = $this->invokeGetData(new $widgetClass);

        $this->assertArrayHasKey('datasets', $data);
        $this->assertArrayHasKey('labels', $data);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Per-chart populated tests — fixtures exercise the same SQL aggregate
    // the Report contract uses, so the chart and the table stay consistent.
    // ─────────────────────────────────────────────────────────────────────────

    public function test_revenue_by_day_chart_aggregates_confirmed_payments_per_day(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);

        // 2 confirmed orders → 2 confirmed payments → 2 days of revenue.
        foreach ([100.00, 50.00] as $amount) {
            $order = Order::factory()->forCustomer($customer)->create([
                'status' => 'paid',
                'total_amount' => $amount,
            ]);
            Payment::factory()->confirmed($admin)->create([
                'order_id' => $order->id,
                'confirmed_at' => now()->subDays(rand(1, 10)),
            ]);
        }

        $widget = new RevenueByDayChart;
        $widget->from = now()->subDays(29)->toDateString();
        $widget->to = now()->toDateString();

        $data = $this->invokeGetData($widget);

        $this->assertCount(1, $data['datasets']);
        $this->assertCount(2, $data['labels']);
        // Each day's revenue lands in the dataset in some order.
        $this->assertEqualsCanonicalizing([100.0, 50.0], $data['datasets'][0]['data']);
    }

    public function test_order_status_distribution_chart_groups_orders_by_status(): void
    {
        Order::factory()->count(3)->create(['status' => 'paid']);
        Order::factory()->count(2)->create(['status' => 'pending']);
        Order::factory()->count(1)->create(['status' => 'cancelled']);

        $data = $this->invokeGetData(new OrderStatusDistributionChart);

        $this->assertCount(1, $data['datasets']);
        $this->assertCount(3, $data['labels']); // 3 distinct statuses
        $this->assertEqualsCanonicalizing([3, 2, 1], $data['datasets'][0]['data']);
    }

    public function test_refund_rate_chart_compares_totals_against_rejections(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        Order::factory()->count(4)->forCustomer($customer)->create(['status' => 'paid']);
        Order::factory()->count(2)->forCustomer($customer)->create(['status' => 'cancelled']);

        // Attach payments to the existing orders (the factory's default
        // order_id => Order::factory() would inflate the orders table).
        $paidOrders = Order::where('status', 'paid')->get();
        Payment::factory()->create([
            'order_id' => $paidOrders[0]->id,
            'status' => 'rejected',
        ]);
        Payment::factory()->create(['order_id' => $paidOrders[1]->id, 'status' => 'pending']);
        Payment::factory()->create(['order_id' => $paidOrders[2]->id, 'status' => 'pending']);
        Payment::factory()->create(['order_id' => $paidOrders[3]->id, 'status' => 'pending']);

        $widget = new RefundRateChart;
        $widget->from = now()->subDays(29)->toDateString();
        $widget->to = now()->toDateString();

        $data = $this->invokeGetData($widget);

        $this->assertCount(2, $data['datasets'], 'two categories: Total and Cancelled/Rejected');
        $this->assertSame(['Orders', 'Payments'], $data['labels']);

        $totals = $data['datasets'][0]['data'];    // 6 orders, 4 payments
        $rejected = $data['datasets'][1]['data']; // 2 cancelled, 1 rejected

        $this->assertSame([6, 4], $totals);
        $this->assertSame([2, 1], $rejected);
    }

    public function test_revenue_by_designer_chart_aggregates_per_designer(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);

        $designerUser = User::factory()->create(['role' => 'designer']);
        $designer = DesignerProfile::factory()->create(['user_id' => $designerUser->id]);
        $design = Design::factory()->create([
            'designer_id' => $designer->id,
            'status' => 'published',
        ]);
        $mapping = DesignProductMapping::factory()->create([
            'design_id' => $design->id,
            'final_price' => 25.00,
        ]);

        $order = Order::factory()->forCustomer($customer)->create([
            'status' => 'paid',
            'total_amount' => 100.00,
        ]);
        Payment::factory()->confirmed($admin)->create([
            'order_id' => $order->id,
            'confirmed_at' => now()->subDay(),
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'design_product_mapping_id' => $mapping->id,
        ]);

        $widget = new RevenueByDesignerChart;
        $widget->from = now()->subDays(29)->toDateString();
        $widget->to = now()->toDateString();

        $data = $this->invokeGetData($widget);

        $this->assertCount(1, $data['datasets']);
        $this->assertCount(1, $data['labels']);
        $this->assertSame(100.0, $data['datasets'][0]['data'][0]);
        $this->assertSame($designerUser->name, $data['labels'][0]);
    }

    public function test_revenue_by_printer_chart_aggregates_per_printer(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $printerUser = User::factory()->create(['role' => 'printer_provider']);
        $printer = PrinterProviderProfile::factory()->create([
            'user_id' => $printerUser->id,
            'company_name' => 'Acme Print Co',
        ]);

        $order = Order::factory()->forCustomer($customer)->create([
            'status' => 'paid',
            'total_amount' => 75.00,
        ]);
        Payment::factory()->confirmed($admin)->create([
            'order_id' => $order->id,
            'confirmed_at' => now()->subDay(),
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'printer_provider_id' => $printer->id,
        ]);

        $widget = new RevenueByPrinterChart;
        $widget->from = now()->subDays(29)->toDateString();
        $widget->to = now()->toDateString();

        $data = $this->invokeGetData($widget);

        $this->assertCount(1, $data['datasets']);
        $this->assertSame(75.0, $data['datasets'][0]['data'][0]);
        $this->assertSame('Acme Print Co', $data['labels'][0]);
    }

    public function test_top_designs_chart_counts_sales_per_design(): void
    {
        $designer = DesignerProfile::factory()->create();
        $design = Design::factory()->create([
            'designer_id' => $designer->id,
            'title' => 'Sunset Tee',
            'status' => 'published',
        ]);
        $mapping = DesignProductMapping::factory()->create([
            'design_id' => $design->id,
        ]);

        OrderItem::factory()->count(3)->create([
            'design_product_mapping_id' => $mapping->id,
        ]);

        $data = $this->invokeGetData(new TopDesignsChart);

        $this->assertCount(1, $data['datasets']);
        $this->assertSame([3], $data['datasets'][0]['data']);
        $this->assertSame(['Sunset Tee'], $data['labels']);
    }

    public function test_customer_ltv_chart_aggregates_lifetime_spend(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create([
            'role' => 'customer',
            'name' => 'Big Spender',
        ]);

        foreach ([100.00, 50.00] as $amount) {
            $order = Order::factory()->forCustomer($customer)->create([
                'status' => 'paid',
                'total_amount' => $amount,
            ]);
            Payment::factory()->confirmed($admin)->create([
                'order_id' => $order->id,
            ]);
        }

        $data = $this->invokeGetData(new CustomerLtvChart);

        $this->assertCount(1, $data['datasets']);
        $this->assertSame([150.0], $data['datasets'][0]['data']);
        $this->assertSame(['Big Spender'], $data['labels']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Page ↔ widget wiring — BaseReportPage must declare the right chart(s)
    // and expose them through getViewData() so the Blade can render them.
    // ─────────────────────────────────────────────────────────────────────────

    #[DataProvider('chartedPages')]
    public function test_base_report_page_declares_its_chart_widgets(
        string $pageClass,
        array $expectedWidgets,
    ): void {
        $page = new $pageClass;

        $reflection = new \ReflectionMethod($page, 'chartWidgets');
        $reflection->setAccessible(true);

        $this->assertSame($expectedWidgets, $reflection->invoke($page));
    }

    #[DataProvider('chartedPages')]
    public function test_get_view_data_exposes_chart_widgets(
        string $pageClass,
        array $expectedWidgets,
    ): void {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin);

        $page = new $pageClass;
        $page->mount();

        $viewData = $page->getViewData();

        $this->assertArrayHasKey('chartWidgets', $viewData);
        $this->assertSame($expectedWidgets, $viewData['chartWidgets']);
    }

    public function test_overview_report_declares_no_chart_widgets(): void
    {
        $page = new OverviewReport;

        $reflection = new \ReflectionMethod($page, 'chartWidgets');
        $reflection->setAccessible(true);

        $this->assertSame([], $reflection->invoke($page));
    }

    public function test_overview_report_view_data_carries_empty_chart_widget_list(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin);

        $page = new OverviewReport;
        $page->mount();

        $this->assertSame([], $page->getViewData()['chartWidgets']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Brutalist style — every chart that uses the trait must inherit the
    // dark-ink options so text stays legible against the sand-100 body.
    // ─────────────────────────────────────────────────────────────────────────

    #[DataProvider('chartWidgets')]
    public function test_chart_widget_applies_brutalist_styling(string $widgetClass): void
    {
        $widget = new $widgetClass;

        $this->assertTrue(
            method_exists($widget, 'getOptions'),
            "{$widgetClass} must expose getOptions() so the brutalist style applies",
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // End-to-end — each charted report page should still render successfully
    // (Livewire + Filament view boot) now that the widget is in the view
    // data. Empty-database render is fine; we just want the page to 200.
    // ─────────────────────────────────────────────────────────────────────────

    #[DataProvider('chartedPages')]
    public function test_charted_report_page_renders_successfully(string $pageClass): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin);

        Livewire::test($pageClass)->assertSuccessful();
    }
}
