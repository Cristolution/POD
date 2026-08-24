<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RevenueTodayStat extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        // Revenue is sourced from confirmed-order totals because `payments`
        // does not carry an `amount` column — the canonical amount lives on
        // the parent order (`orders.total_amount`).
        $today = Order::query()
            ->whereHas('payments', fn ($q) => $q->where('status', 'confirmed')->whereDate('confirmed_at', today()))
            ->sum('total_amount');

        return [
            Stat::make('Revenue today', '$'.number_format((float) $today, 2))
                ->description('Confirmed payments received today')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
        ];
    }
}
