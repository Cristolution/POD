<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Reports\Admin\AverageOrderValueReport;
use App\Reports\Admin\CustomerLtvReport;
use App\Reports\Admin\OrderItemStatusDistributionReport;
use App\Reports\Admin\OrderStatusDistributionReport;
use App\Reports\Admin\PlatformOverviewReport;
use App\Reports\Admin\RefundCancellationRateReport;
use App\Reports\Admin\RevenueByDayReport;
use App\Reports\Admin\RevenueByDesignerReport;
use App\Reports\Admin\RevenueByPrinterReport;
use App\Reports\Admin\TopDesignsReport;
use App\Reports\Contracts\Report;
use App\Reports\CsvExporter;
use App\Reports\Customer\OrderHistoryReport;
use App\Reports\Designer\DashboardReport;
use App\Reports\Designer\RevenueByDesignReport;
use App\Reports\Printer\PayoutReport;
use App\Reports\Printer\WorkQueueReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    /** @var array<string,array{class: class-string<Report>, auth: string}> */
    private const REPORTS = [
        'admin/overview' => [PlatformOverviewReport::class, 'admin'],
        'admin/revenue-by-day' => [RevenueByDayReport::class, 'admin'],
        'admin/revenue-by-designer' => [RevenueByDesignerReport::class, 'admin'],
        'admin/revenue-by-printer' => [RevenueByPrinterReport::class, 'admin'],
        'admin/order-status-distribution' => [OrderStatusDistributionReport::class, 'admin'],
        'admin/order-item-status-distribution' => [OrderItemStatusDistributionReport::class, 'admin'],
        'admin/top-designs' => [TopDesignsReport::class, 'admin'],
        'admin/customer-ltv' => [CustomerLtvReport::class, 'admin'],
        'admin/average-order-value' => [AverageOrderValueReport::class, 'admin'],
        'admin/refund-cancellation-rate' => [RefundCancellationRateReport::class, 'admin'],
        'designer/dashboard' => [DashboardReport::class, 'designer'],
        'designer/revenue-by-design' => [RevenueByDesignReport::class, 'designer'],
        'printer/work-queue' => [WorkQueueReport::class, 'printer_provider'],
        'printer/payout' => [PayoutReport::class, 'printer_provider'],
        'customer/order-history' => [OrderHistoryReport::class, 'customer'],
    ];

    public function __construct(private readonly CsvExporter $csv) {}

    public function show(Request $request, string $reportKey): JsonResponse|StreamedResponse
    {
        abort_unless(isset(self::REPORTS[$reportKey]), 404, 'Unknown report.');

        [$class, $requiredRole] = self::REPORTS[$reportKey];

        $user = $request->user();
        abort_if(! $user instanceof User, 401);
        abort_unless(
            $user->isAdmin() || $user->role === $requiredRole,
            403,
            "Role '{$user->role}' cannot access this report.",
        );

        /** @var Report $report */
        $report = app($class);
        $result = $report->run($request);

        if ($request->string('format')->value() === 'csv') {
            $rows = is_array($result) ? $result : collect($result)->all();
            $safeKey = str_replace('/', '-', $reportKey);
            $filename = "{$safeKey}-".now()->format('Ymd-His').'.csv';

            return $this->csv->stream($report->csvHeaders(), $rows, $filename);
        }

        return response()->json($result);
    }
}
