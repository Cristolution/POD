<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PendingOrdersStat extends BaseWidget
{
    protected static ?int $sort = 3;

    protected function getStats(): array
    {
        $pending = Order::query()
            ->whereIn('status', ['pending', 'confirmed', 'paid'])
            ->count();

        return [
            Stat::make('Pending orders', $pending)
                ->description('Awaiting fulfillment')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('warning'),
        ];
    }
}
