<?php

declare(strict_types=1);

namespace App\Filament\Pages\Reports;

use App\Filament\Widgets\Reports\CustomerLtvChart;
use BackedEnum;

class CustomerLtvReport extends BaseReportPage
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Customer LTV';

    protected static ?string $title = 'Customer lifetime value';

    protected static ?int $navigationSort = 6;

    public function reportClass(): string
    {
        return \App\Reports\Admin\CustomerLtvReport::class;
    }

    /**
     * @return array<int, string>
     */
    protected function chartWidgets(): array
    {
        return [CustomerLtvChart::class];
    }
}
