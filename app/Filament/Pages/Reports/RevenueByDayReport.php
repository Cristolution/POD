<?php

declare(strict_types=1);

namespace App\Filament\Pages\Reports;

use App\Filament\Widgets\Reports\RevenueByDayChart;
use BackedEnum;

class RevenueByDayReport extends BaseReportPage
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Revenue by day';

    protected static ?string $title = 'Revenue by day';

    protected static ?int $navigationSort = 2;

    public function reportClass(): string
    {
        return \App\Reports\Admin\RevenueByDayReport::class;
    }

    /**
     * @return array<int, string>
     */
    protected function chartWidgets(): array
    {
        return [RevenueByDayChart::class];
    }
}
