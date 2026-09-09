<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Widgets\ActionQueueStat;
use App\Filament\Widgets\RecentOrdersTable;
use App\Filament\Widgets\RevenueChart;
use App\Filament\Widgets\SalesTodayStat;
use App\Filament\Widgets\TotalCustomersStat;
use Filament\Pages\Dashboard;
use Filament\Widgets\Widget;

/**
 * KPI dashboard — replaces the default `Filament\Pages\Dashboard` at `/admin`.
 *
 * Layout (v4 `Filament\Pages\Dashboard`):
 *   header widgets → 3 multi-stat widgets (users, sales today, action queue)
 *   footer widgets → revenue chart + recent orders table
 *
 * Each header widget tells a distinct story:
 *   - TotalCustomersStat: WHO uses the platform
 *   - SalesTodayStat:     HOW MUCH money today (with deltas, AOV, refund rate)
 *   - ActionQueueStat:    WHAT needs admin attention (with revenue impact)
 */
class KpiDashboard extends Dashboard
{
    protected static ?string $title = 'POD Admin';

    protected function getHeaderWidgets(): array
    {
        return [
            TotalCustomersStat::class,
            SalesTodayStat::class,
            ActionQueueStat::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            RevenueChart::class,
            RecentOrdersTable::class,
        ];
    }

    /**
     * Suppress the parent `Filament\Pages\Dashboard::getWidgets()`, which defaults
     * to `Filament::getWidgets()` and would re-render every auto-discovered
     * widget from `app/Filament/Widgets/` in the content slot — duplicating the
     * header/footer widgets and dragging in the `Reports/*Chart` widgets that
     * belong on their dedicated report pages, not the admin home.
     *
     * @return array<int, class-string<Widget>>
     */
    public function getWidgets(): array
    {
        return [];
    }
}
