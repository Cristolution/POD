<?php

declare(strict_types=1);

namespace App\Filament\Widgets\Reports;

use App\Filament\Widgets\Concerns\BrutalistChartStyle;
use App\Reports\Admin\RevenueByDesignerReport;
use Filament\Widgets\ChartWidget;
use Illuminate\Http\Request;

/**
 * Horizontal bar chart of confirmed-revenue per designer.
 *
 * Top-10 only — long-tail designers clutter the visual and the report
 * returns the full ranked list anyway.
 */
class RevenueByDesignerChart extends ChartWidget
{
    use BrutalistChartStyle;

    protected static bool $isLazy = false;

    protected ?string $heading = 'Top designers by revenue';

    protected ?string $maxHeight = '380px';

    public ?string $from = null;

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

        $rows = app(RevenueByDesignerReport::class)->run(new Request([
            'from' => $from,
            'to' => $to,
        ]));

        // The report already orders by revenue DESC — slice the top 10.
        $rows = array_slice($rows, 0, 10);

        return [
            'datasets' => [
                [
                    'label' => 'Revenue',
                    'data' => array_map(fn ($r) => (float) $r['revenue'], $rows),
                    'backgroundColor' => '#C84A2C', // primary-700 coral
                ],
            ],
            'labels' => array_map(fn ($r) => (string) $r['designer_name'], $rows),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    /**
     * Horizontal layout: x is the value axis (revenue), y holds the
     * designer names. Swap the axis definitions from the vertical default.
     *
     * @return array<string, mixed>
     */
    protected function getOptions(): array
    {
        $base = $this->brutalistChartOptions();

        $base['indexAxis'] = 'y';

        return $base;
    }
}
