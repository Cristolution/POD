<?php

declare(strict_types=1);

namespace App\Filament\Pages\Reports;

use App\Filament\Widgets\Reports\RevenueByDesignerChart;
use BackedEnum;

class RevenueByDesignerReport extends BaseReportPage
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-paint-brush';

    protected static ?string $navigationLabel = 'Revenue by designer';

    protected static ?string $title = 'Revenue by designer';

    protected static ?int $navigationSort = 3;

    public function reportClass(): string
    {
        return \App\Reports\Admin\RevenueByDesignerReport::class;
    }

    /**
     * @return array<int, string>
     */
    protected function chartWidgets(): array
    {
        return [RevenueByDesignerChart::class];
    }
}
