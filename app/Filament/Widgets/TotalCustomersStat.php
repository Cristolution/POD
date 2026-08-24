<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TotalCustomersStat extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        return [
            Stat::make('Customers', User::where('role', 'customer')->whereNull('deleted_at')->count())
                ->description('Total active customer accounts')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary'),
            Stat::make('Designers', User::where('role', 'designer')->whereNull('deleted_at')->count())
                ->descriptionIcon('heroicon-m-paint-brush')
                ->color('warning'),
            Stat::make('Printers', User::where('role', 'printer_provider')->whereNull('deleted_at')->count())
                ->descriptionIcon('heroicon-m-printer')
                ->color('info'),
        ];
    }
}
