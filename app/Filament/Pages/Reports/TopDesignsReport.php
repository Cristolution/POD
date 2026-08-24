<?php

declare(strict_types=1);

namespace App\Filament\Pages\Reports;

use BackedEnum;

class TopDesignsReport extends BaseReportPage
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-star';

    protected static ?string $navigationLabel = 'Top designs';

    protected static ?string $title = 'Top designs';

    protected static ?int $navigationSort = 5;

    public function reportClass(): string
    {
        return \App\Reports\Admin\TopDesignsReport::class;
    }
}
