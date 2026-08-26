<?php

declare(strict_types=1);

namespace App\Filament\Widgets\Reports;

use App\Filament\Pages\Reports\BaseReportPage;
use App\Filament\Widgets\Concerns\BrutalistChartStyle;
use App\Reports\Admin\RevenueByDayReport;
use Filament\Widgets\ChartWidget;
use Illuminate\Http\Request;

/**
 * Line chart of confirmed-revenue per day for the date range selected
 * on the matching Report page. Shares the dashboard RevenueChart's
 * brutalist styling via {@see BrutalistChartStyle}.
 *
 * The hosting Report page wires its filter values through Livewire mount
 * parameters — see {@see BaseReportPage::renderChart()}.
 */
class RevenueByDayChart extends ChartWidget
{
    use BrutalistChartStyle;

    protected static bool $isLazy = false;

    protected ?string $heading = 'Revenue by day';

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
        // Fall back to the same 30-day default the report uses so the chart
        // still renders on first mount before any input is touched.
        $from = $this->from ?? now()->subDays(29)->toDateString();
        $to = $this->to ?? now()->toDateString();

        $rows = app(RevenueByDayReport::class)->run(new Request([
            'from' => $from,
            'to' => $to,
        ]));

        return [
            'datasets' => [
                [
                    'label' => 'Revenue',
                    'data' => array_map(fn ($r) => (float) $r['revenue'], $rows),
                    'fill' => true,
                ],
            ],
            'labels' => array_map(fn ($r) => (string) $r['date'], $rows),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
