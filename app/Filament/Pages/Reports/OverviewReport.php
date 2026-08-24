<?php

declare(strict_types=1);

namespace App\Filament\Pages\Reports;

use App\Reports\Admin\PlatformOverviewReport;
use BackedEnum;

class OverviewReport extends BaseReportPage
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Overview';

    protected static ?string $title = 'Platform overview';

    protected static ?int $navigationSort = 1;

    public function reportClass(): string
    {
        return PlatformOverviewReport::class;
    }
}
