<?php

declare(strict_types=1);

namespace App\Filament\Pages\Reports;

use App\Filament\Widgets\Reports\OrderStatusDistributionChart;
use BackedEnum;

class OrderStatusDistributionReport extends BaseReportPage
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Order status distribution';

    protected static ?string $title = 'Order status distribution';

    protected static ?int $navigationSort = 7;

    public function reportClass(): string
    {
        return \App\Reports\Admin\OrderStatusDistributionReport::class;
    }

    /**
     * @return array<int, string>
     */
    protected function chartWidgets(): array
    {
        return [OrderStatusDistributionChart::class];
    }
}
