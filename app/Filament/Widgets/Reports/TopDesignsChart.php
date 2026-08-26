<?php

declare(strict_types=1);

namespace App\Filament\Widgets\Reports;

use App\Filament\Widgets\Concerns\BrutalistChartStyle;
use App\Reports\Admin\TopDesignsReport;
use Filament\Widgets\ChartWidget;
use Illuminate\Http\Request;

/**
 * Horizontal bar chart of the 20 best-selling designs by order-item count.
 * The Report caps at 20 — no slicing needed.
 */
class TopDesignsChart extends ChartWidget
{
    use BrutalistChartStyle;

    protected static bool $isLazy = false;

    protected ?string $heading = 'Best-selling designs';

    protected ?string $maxHeight = '480px';

    public function getData(): array
    {
        $rows = app(TopDesignsReport::class)->run(new Request);

        return [
            'datasets' => [
                [
                    'label' => 'Sales',
                    'data' => array_map(fn ($r) => (int) $r['sales_count'], $rows),
                    'backgroundColor' => '#C84A2C',
                ],
            ],
            'labels' => array_map(fn ($r) => (string) $r['design_title'], $rows),
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
