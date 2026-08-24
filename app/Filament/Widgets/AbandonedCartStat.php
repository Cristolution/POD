<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\CartItem;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AbandonedCartStat extends BaseWidget
{
    protected static ?int $sort = 4;

    protected function getStats(): array
    {
        $abandoned = CartItem::query()
            ->where('created_at', '<=', now()->subHours(24))
            ->count();

        return [
            Stat::make('Abandoned carts', $abandoned)
                ->description('Items in cart > 24h old')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('danger'),
        ];
    }
}
