<?php

declare(strict_types=1);

namespace App\Filament\Widgets\Reports;

use App\Filament\Widgets\Concerns\BrutalistChartStyle;
use App\Reports\Admin\RevenueByPrinterReport;
use Filament\Widgets\ChartWidget;
use Illuminate\Http\Request;

/**
 * Horizontal bar chart of confirmed-revenue per printer (top 10).
 */
class RevenueByPrinterChart extends ChartWidget
{
    use BrutalistChartStyle;

    protected static bool $isLazy = false;

    protected ?string $heading = 'Top printers by revenue';

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

        $rows = app(RevenueByPrinterReport::class)->run(new Request([
            'from' => $from,
            'to' => $to,
        ]));

        $rows = array_slice($rows, 0, 10);

        return [
            'datasets' => [
                [
                    'label' => 'Revenue',
                    'data' => array_map(fn ($r) => (float) $r['revenue'], $rows),
                    'backgroundColor' => '#C84A2C',
                ],
            ],
            'labels' => array_map(fn ($r) => (string) $r['printer_name'], $rows),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        $base = $this->brutalistChartOptions();

        $base['indexAxis'] = 'y';

        return $base;
    }
}
