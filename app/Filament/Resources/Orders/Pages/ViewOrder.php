<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders\Pages;

use App\Actions\Order\CancelOrderAction;
use App\Filament\Resources\Orders\OrderResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('cancel')
                ->label('Cancel order')
                ->color('danger')
                ->visible(fn (): bool => ! in_array($this->record->status, ['cancelled', 'delivered'], true))
                ->requiresConfirmation()
                ->action(fn () => app(CancelOrderAction::class)->execute($this->record))
                ->after(fn () => $this->refresh()),

            Action::make('markDelivered')
                ->label('Mark delivered')
                ->color('success')
                ->visible(fn (): bool => $this->record->status === 'shipped')
                ->requiresConfirmation()
                ->action(function (): void {
                    $this->record->update(['status' => 'delivered']);
                })
                ->after(fn () => $this->refresh()),
        ];
    }
}
