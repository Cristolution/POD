<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Today's commercial snapshot — replaces the original single-stat
 * RevenueTodayStat with a multi-stat widget that gives the admin
 * real context (deltas vs yesterday, AOV, refund rate).
 */
class SalesTodayStat extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        // Revenue is sourced from confirmed-order totals because `payments`
        // does not carry an `amount` column — the canonical amount lives on
        // the parent order (`orders.total_amount`).
        $revenueToday = (float) Order::query()
            ->whereHas('payments', fn ($q) => $q->where('status', 'confirmed')->whereDate('confirmed_at', today()))
            ->sum('total_amount');

        $revenueYesterday = (float) Order::query()
            ->whereHas('payments', fn ($q) => $q->where('status', 'confirmed')->whereDate('confirmed_at', today()->subDay()))
            ->sum('total_amount');

        $revenueDelta = $this->deltaPercent($revenueToday, $revenueYesterday);

        $ordersToday = Order::query()
            ->whereHas('payments', fn ($q) => $q->where('status', 'confirmed')->whereDate('confirmed_at', today()))
            ->count();

        $ordersYesterday = Order::query()
            ->whereHas('payments', fn ($q) => $q->where('status', 'confirmed')->whereDate('confirmed_at', today()->subDay()))
            ->count();

        $ordersDelta = $this->deltaPercent($ordersToday, $ordersYesterday);

        $aov = $ordersToday > 0 ? $revenueToday / $ordersToday : 0.0;

        // Refund rate over the last 7 days — counts orders that ended in
        // `cancelled` (the only customer-facing refund path today).
        $orders7d = Order::query()->where('created_at', '>=', now()->subDays(7))->count();
        $cancelled7d = Order::query()
            ->where('created_at', '>=', now()->subDays(7))
            ->where('status', 'cancelled')
            ->count();
        $refundRate = $orders7d > 0 ? ($cancelled7d / $orders7d) * 100 : 0.0;

        return [
            Stat::make('Revenue today', '$'.number_format($revenueToday, 2))
                ->description($this->deltaDescription($revenueDelta, 'vs yesterday'))
                ->descriptionIcon($this->deltaIcon($revenueDelta))
                ->color($this->deltaColor($revenueDelta)),

            Stat::make('Orders today', $ordersToday)
                ->description($this->deltaDescription($ordersDelta, 'vs yesterday'))
                ->descriptionIcon($this->deltaIcon($ordersDelta))
                ->color($this->deltaColor($ordersDelta)),

            Stat::make('Avg order value', '$'.number_format($aov, 2))
                ->description("Today's confirmed orders")
                ->descriptionIcon('heroicon-m-calculator')
                ->color('info'),

            Stat::make('Refund rate (7d)', number_format($refundRate, 1).'%')
                ->description("{$cancelled7d} cancelled of {$orders7d} orders")
                ->descriptionIcon('heroicon-m-arrow-uturn-left')
                ->color($refundRate > 10 ? 'danger' : ($refundRate > 5 ? 'warning' : 'success')),
        ];
    }

    private function deltaPercent(float|int $current, float|int $previous): float
    {
        if ($previous <= 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return (($current - $previous) / $previous) * 100;
    }

    private function deltaDescription(float $pct, string $suffix): string
    {
        if ($pct == 0.0) {
            return "No change {$suffix}";
        }

        $sign = $pct > 0 ? '+' : '';
        $arrow = $pct > 0 ? '▲' : '▼';

        return "{$sign}".number_format($pct, 1)."% {$arrow} {$suffix}";
    }

    private function deltaIcon(float $pct): string
    {
        if ($pct > 0) {
            return 'heroicon-m-arrow-trending-up';
        }
        if ($pct < 0) {
            return 'heroicon-m-arrow-trending-down';
        }

        return 'heroicon-m-minus';
    }

    private function deltaColor(float $pct): string
    {
        if ($pct > 0) {
            return 'success';
        }
        if ($pct < 0) {
            return 'danger';
        }

        return 'gray';
    }
}
