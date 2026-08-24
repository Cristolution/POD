<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Widgets\AbandonedCartStat;
use App\Filament\Widgets\PendingOrdersStat;
use App\Filament\Widgets\RecentOrdersTable;
use App\Filament\Widgets\RevenueChart;
use App\Filament\Widgets\RevenueTodayStat;
use App\Filament\Widgets\TotalCustomersStat;
use Filament\Pages\Dashboard;

/**
 * KPI dashboard — replaces the default `Filament\Pages\Dashboard` at `/admin`.
 *
 * Layout (v4 `Filament\Pages\Dashboard`):
 *   header widgets → 4 stat widgets (customers, revenue today, pending orders, abandoned cart)
 *   footer widgets → revenue chart + recent orders table
 */
class KpiDashboard extends Dashboard
{
    protected static ?string $title = 'POD Admin';

    protected function getHeaderWidgets(): array
    {
        return [
            TotalCustomersStat::class,
            RevenueTodayStat::class,
            PendingOrdersStat::class,
            AbandonedCartStat::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            RevenueChart::class,
            RecentOrdersTable::class,
        ];
    }
}
