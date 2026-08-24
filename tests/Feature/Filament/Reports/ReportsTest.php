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
use App\Models\User;
use App\Reports\Admin\PlatformOverviewReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Map of admin-slug → page FQCN for the 8 concrete Report pages whose
     * backing Phase 2 Report classes exist AND are admin-scoped.
     *
     * Excluded from this list:
     *  - PrinterPayoutReport / WorkQueueReport: their backing Reports call
     *    `abort_if(! $user->isPrinterProvider())`, so they 403 from an admin
     *    context. They are printer-scoped, not admin-scoped — they belong on
     *    the printer dashboard, not the admin panel.
     *  - ConversionFunnelReport, CartAbandonmentReport, StuckPaymentsReport,
     *    StuckShipmentsReport: Phase 2 Report classes were never built.
     *  - DesignerPayoutReport: backing `App\Reports\Designer\PayoutReport`
     *    was never built.
     *
     * @return array<string, array{0: string, 1: class-string<BaseReportPage>}>
     */
    public static function reportPages(): array
    {
        return [
            ['overview-report', OverviewReport::class],
            ['revenue-by-day-report', RevenueByDayReport::class],
            ['revenue-by-designer-report', RevenueByDesignerReport::class],
            ['revenue-by-printer-report', RevenueByPrinterReport::class],
            ['top-designs-report', TopDesignsReport::class],
            ['customer-ltv-report', CustomerLtvReport::class],
            ['order-status-distribution-report', OrderStatusDistributionReport::class],
            ['refund-rate-report', RefundRateReport::class],
        ];
    }

    #[DataProvider('reportPages')]
    public function test_admin_can_view_report_page(string $slug, string $pageClass): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get('/admin/'.$slug)
            ->assertOk();
    }

    #[DataProvider('reportPages')]
    public function test_non_admin_cannot_view_report_page(string $slug): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer)
            ->get('/admin/'.$slug)
            ->assertForbidden();
    }

    public function test_admin_can_view_csv_export_link(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get('/admin/overview-report')
            ->assertOk()
            ->assertSee('Download CSV');
    }

    public function test_overview_report_csv_export_streams_csv_response(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(OverviewReport::class)
            ->call('exportCsv')
            ->assertFileDownloaded(
                'overview-report-'.now()->format('Ymd-His').'.csv',
                null,
                'text/csv',
            );
    }

    public function test_report_page_renders_table_headers_from_first_row(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // OverviewReport returns aggregate metrics even with no data, so the table
        // headers will be derived from its first row's keys.
        $this->actingAs($admin)
            ->get('/admin/overview-report')
            ->assertOk()
            ->assertSee('Metric')
            ->assertSee('Value');
    }

    public function test_base_report_page_resolves_a_phase2_report_class(): void
    {
        $page = new OverviewReport;

        $report = $page->reportClass();

        $this->assertSame(PlatformOverviewReport::class, $report);
    }

    public function test_base_report_page_subclasses_implement_report_class(): void
    {
        foreach (self::reportPages() as [$slug, $pageClass]) {
            $page = new $pageClass;
            $this->assertInstanceOf(BaseReportPage::class, $page);
            $this->assertNotEmpty($page->reportClass(), "{$pageClass}::reportClass() must return a class-string");
        }
    }
}
