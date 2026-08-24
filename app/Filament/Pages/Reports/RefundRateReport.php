<?php

declare(strict_types=1);

namespace App\Filament\Pages\Reports;

use App\Reports\Admin\RefundCancellationRateReport;
use BackedEnum;

class RefundRateReport extends BaseReportPage
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-uturn-left';

    protected static ?string $navigationLabel = 'Refund & cancellation rate';

    protected static ?string $title = 'Refund & cancellation rate';

    protected static ?int $navigationSort = 8;

    public function reportClass(): string
    {
        return RefundCancellationRateReport::class;
    }
}
