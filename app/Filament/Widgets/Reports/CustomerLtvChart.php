<?php

declare(strict_types=1);

namespace App\Filament\Widgets\Reports;

use App\Filament\Widgets\Concerns\BrutalistChartStyle;
use App\Reports\Admin\CustomerLtvReport;
use Filament\Widgets\ChartWidget;
use Illuminate\Http\Request;

/**
 * Horizontal bar chart of the top 10 customers by lifetime spend.
 * The Report already caps at 50 — slice for chart legibility.
 */
class CustomerLtvChart extends ChartWidget
{
    use BrutalistChartStyle;

    protected static bool $isLazy = false;

    protected ?string $heading = 'Top customers by lifetime spend';

    protected ?string $maxHeight = '420px';

    public function getData(): array
    {
        $rows = app(CustomerLtvReport::class)->run(new Request);

        $rows = array_slice($rows, 0, 10);

        return [
            'datasets' => [
                [
                    'label' => 'Lifetime spend',
                    'data' => array_map(fn ($r) => (float) $r['total_spent'], $rows),
                    'backgroundColor' => '#C84A2C',
                ],
            ],
            'labels' => array_map(fn ($r) => (string) $r['customer_name'], $rows),
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
