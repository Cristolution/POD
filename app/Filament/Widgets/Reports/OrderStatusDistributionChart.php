<?php

declare(strict_types=1);

namespace App\Filament\Widgets\Reports;

use App\Filament\Widgets\Concerns\BrutalistChartStyle;
use App\Reports\Admin\OrderStatusDistributionReport;
use Filament\Widgets\ChartWidget;
use Illuminate\Http\Request;

/**
 * Doughnut chart of order counts bucketed by status.
 *
 * Palette is hand-picked to keep the brutalist vibe — solid coral / ink /
 * sand-tone slices instead of the rainbow default Filament ships.
 */
class OrderStatusDistributionChart extends ChartWidget
{
    use BrutalistChartStyle;

    protected static bool $isLazy = false;

    protected ?string $heading = 'Orders by status';

    protected ?string $maxHeight = '320px';

    public function getData(): array
    {
        $rows = app(OrderStatusDistributionReport::class)->run(new Request);

        return [
            'datasets' => [
                [
                    'label' => 'Orders',
                    'data' => array_map(fn ($r) => (int) $r['count'], $rows),
                    // Brutalist palette — coral primary, ink, then warm greys
                    // of decreasing warmth. Stays legible against sand-100.
                    'backgroundColor' => [
                        '#C84A2C', // primary-700 (coral)
                        '#1a1a1a', // gray-800 (ink)
                        '#8a6f3f', // warm khaki
                        '#a89b85', // gray-400
                        '#d6c69a', // sand
                        '#5a4530', // deep brown
                    ],
                    'borderColor' => '#1a1a1a',
                    'borderWidth' => 3,
                ],
            ],
            'labels' => array_map(fn ($r) => ucwords((string) $r['status']), $rows),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    /**
     * Doughnut charts need a hidden legend axis system — the base trait's
     * `scales` block throws Chart.js off when there are no x/y axes.
     *
     * @return array<string, mixed>
     */
    protected function getOptions(): array
    {
        return [
            'plugins' => $this->brutalistChartOptions()['plugins'],
            'cutout' => '55%',
        ];
    }
}
