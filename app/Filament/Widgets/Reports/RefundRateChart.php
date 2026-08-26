<?php

declare(strict_types=1);

namespace App\Filament\Widgets\Reports;

use App\Filament\Widgets\Concerns\BrutalistChartStyle;
use App\Reports\Admin\RefundCancellationRateReport;
use Filament\Widgets\ChartWidget;
use Illuminate\Http\Request;

/**
 * Grouped bar chart of cancellations / rejections vs totals for the
 * selected date range.
 *
 * Four bars per page-load, two categories:
 *   Orders: total | cancelled
 *   Payments: total | rejected
 */
class RefundRateChart extends ChartWidget
{
    use BrutalistChartStyle;

    protected static bool $isLazy = false;

    protected ?string $heading = 'Refunds & rejections';

    protected ?string $maxHeight = '320px';

    /** Bound from the parent Report page via Livewire params. */
    public ?string $from = null;

    /** Bound from the parent Report page via Livewire params. */
    public ?string $to = null;

    public function mount(?string $from = null, ?string $to = null): void
    {
        $this->from = $from;
        $this->to = $to;
    }

    public function getData(): array
    {
        $from = $this->from ?? now()->subDays(29)->toDateString();
        $to = $this->to ?? now()->toDateString();

        $rows = app(RefundCancellationRateReport::class)->run(new Request([
            'from' => $from,
            'to' => $to,
        ]));

        // Build a metric → count lookup so we don't depend on row order.
        $by = [];
        foreach ($rows as $r) {
            $by[(string) $r['metric']] = (int) $r['count'];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total',
                    'data' => [$by['order_total'] ?? 0, $by['payment_total'] ?? 0],
                    'backgroundColor' => '#a89b85', // gray-400 sand
                ],
                [
                    'label' => 'Cancelled / rejected',
                    'data' => [$by['order_cancelled'] ?? 0, $by['payment_rejected'] ?? 0],
                    'backgroundColor' => '#C84A2C', // primary-700 coral
                ],
            ],
            'labels' => ['Orders', 'Payments'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
