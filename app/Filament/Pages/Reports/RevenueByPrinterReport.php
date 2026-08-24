<?php

declare(strict_types=1);

namespace App\Filament\Pages\Reports;

use BackedEnum;

class RevenueByPrinterReport extends BaseReportPage
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-printer';

    protected static ?string $navigationLabel = 'Revenue by printer';

    protected static ?string $title = 'Revenue by printer';

    protected static ?int $navigationSort = 4;

    public function reportClass(): string
    {
        return \App\Reports\Admin\RevenueByPrinterReport::class;
    }
}
