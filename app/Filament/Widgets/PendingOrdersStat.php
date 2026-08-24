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
        // Anything pre-shipment: paid and processing are already on the books
        // but have not yet been handed off to a printer.
        $pending = Order::query()
            ->whereIn('status', ['pending', 'paid', 'processing'])
            ->count();

        return [
            Stat::make('Pending orders', $pending)
                ->description('Awaiting fulfillment')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('warning'),
        ];
    }
}
