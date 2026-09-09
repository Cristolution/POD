<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\BrutalistChartStyle;
use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class RevenueChart extends ChartWidget
{
    use BrutalistChartStyle;

    /** Render immediately (no lazy placeholder) — the Trend aggregate query is cheap. */
    protected static bool $isLazy = false;

    protected ?string $heading = 'Revenue (last 30 days)';

    /** Span the full footer row so the 30-day trend has room to breathe. */
    protected int|string|array $columnSpan = 'full';

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
}
