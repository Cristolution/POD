<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class RevenueChart extends ChartWidget
{
    /** Render immediately (no lazy placeholder) — the Trend aggregate query is cheap. */
    protected static bool $isLazy = false;

    protected ?string $heading = 'Revenue (last 30 days)';

    protected function getData(): array
    {
        // Revenue = sum of `orders.total_amount` for orders with at least one
        // confirmed payment, grouped by the order's `created_at` date.
        // (The payments table has no `amount` column, so we cannot trend on it.)
        $data = Trend::query(
            Order::query()
                ->whereHas('payments', fn ($q) => $q->where('status', 'confirmed')),
        )
            ->between(start: now()->subDays(30), end: now())
            ->perDay()
            ->sum('total_amount');

        return [
            'datasets' => [
                [
                    'label' => 'Revenue',
                    'data' => $data->map(fn (TrendValue $value): float => (float) $value->aggregate)->toArray(),
                ],
            ],
            'labels' => $data->map(fn (TrendValue $value): string => $value->date)->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    /**
     * Chart.js options — force dark ink on all canvas-rendered text (axes, legend,
     * tooltips) so contrast on the sand-100 body stays WCAG-compliant.
     * Filament's defaults ship text in gray-500 (rgb(107,114,128)) which is borderline.
     */
    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'labels' => [
                        'color' => '#1a1a1a', // --color-gray-800 ink
                        'font' => [
                            'family' => 'Work Sans, ui-sans-serif, system-ui, sans-serif',
                            'weight' => '600',
                        ],
                    ],
                ],
                'tooltip' => [
                    'backgroundColor' => '#ffffff',
                    'borderColor' => '#1a1a1a',
                    'borderWidth' => 3,
                    'titleColor' => '#0a0a0a',
                    'bodyColor' => '#1a1a1a',
                    'titleFont' => [
                        'family' => 'Archivo Black, sans-serif',
                        'weight' => '900',
                    ],
                    'bodyFont' => [
                        'family' => 'Work Sans, ui-sans-serif, system-ui, sans-serif',
                        'weight' => '600',
                    ],
                ],
            ],
            'scales' => [
                'x' => [
                    'ticks' => [
                        'color' => '#1a1a1a',
                        'font' => [
                            'family' => 'Space Mono, ui-monospace, monospace',
                            'weight' => '700',
                        ],
                    ],
                    'grid' => [
                        'color' => 'rgba(168, 155, 133, 0.25)', // --color-gray-400 with alpha
                        'lineWidth' => 1,
                    ],
                    'border' => [
                        'color' => '#1a1a1a',
                        'width' => 2,
                    ],
                ],
                'y' => [
                    'ticks' => [
                        'color' => '#1a1a1a',
                        'font' => [
                            'family' => 'Space Mono, ui-monospace, monospace',
                            'weight' => '700',
                        ],
                    ],
                    'grid' => [
                        'color' => 'rgba(168, 155, 133, 0.25)',
                        'lineWidth' => 1,
                    ],
                    'border' => [
                        'color' => '#1a1a1a',
                        'width' => 2,
                    ],
                ],
            ],
            'elements' => [
                'line' => [
                    'borderWidth' => 3,
                    'borderColor' => '#C84A2C', // --color-primary-700 (AA on white)
                    'backgroundColor' => 'rgba(200, 74, 44, 0.15)',
                ],
                'point' => [
                    'backgroundColor' => '#C84A2C',
                    'borderColor' => '#1a1a1a',
                    'borderWidth' => 2,
                    'radius' => 4,
                    'hoverRadius' => 6,
                ],
            ],
        ];
    }
}
