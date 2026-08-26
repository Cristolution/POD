<?php

declare(strict_types=1);

namespace App\Filament\Widgets\Concerns;

use App\Filament\Widgets\Reports;
use App\Filament\Widgets\RevenueChart;

/**
 * Brutalist chart styling for the Sand + Coral theme.
 *
 * Filament's defaults ship chart text in gray-500, which sits below WCAG AA
 * on a sand-100 body. Every chart in the admin panel inherits this trait so
 * ink stays dark, gridlines stay subtle, and tooltips stay legible.
 *
 * Used by {@see RevenueChart} (dashboard) and the
 * seven report chart widgets in {@see Reports}.
 */
trait BrutalistChartStyle
{
    /**
     * Sand-theme Chart.js options.
     *
     * Subclasses can override the entire array via `getOptions()` if they
     * need horizontal bars, hidden legend, or anything bespoke; otherwise
     * they inherit this verbatim.
     *
     * @return array<string, mixed>
     */
    protected function brutalistChartOptions(): array
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
                'bar' => [
                    'borderColor' => '#1a1a1a',
                    'borderWidth' => 2,
                    'backgroundColor' => 'rgba(200, 74, 44, 0.85)',
                ],
            ],
        ];
    }

    /**
     * Default Chart.js options for any chart that uses this trait.
     * Subclasses override this when they need horizontal bars, no legend, etc.
     *
     * @return array<string, mixed>
     */
    protected function getOptions(): array
    {
        return $this->brutalistChartOptions();
    }
}
