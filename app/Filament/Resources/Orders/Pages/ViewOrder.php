<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders\Pages;

use App\Actions\Order\CancelOrderAction;
use App\Actions\Order\MarkOrderDeliveredAction;
use App\Actions\Order\MarkOrderPaidAction;
use App\Actions\Order\MarkOrderProcessingAction;
use App\Actions\Order\MarkOrderShippedAction;
use App\Filament\Resources\Orders\OrderResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // pending → paid (admin confirms payment)
            Action::make('markPaid')
                ->label('Mark paid')
                ->color('primary')
                ->icon('heroicon-o-credit-card')
                ->visible(fn (): bool => $this->record->status === 'pending')
                ->requiresConfirmation()
                ->modalHeading('Mark order as paid?')
                ->modalDescription('This moves all line items to "received" so the printer queue lights up.')
                ->action(function (): void {
                    app(MarkOrderPaidAction::class)->execute($this->record);
                    Notification::make()->title('Order marked paid')->success()->send();
                })
                ->after(fn () => $this->refresh()),

            // paid → processing (admin confirms production started)
            Action::make('markProcessing')
                ->label('Mark processing')
                ->color('warning')
                ->icon('heroicon-o-cog-6-tooth')
                ->visible(fn (): bool => $this->record->status === 'paid')
                ->requiresConfirmation()
                ->modalHeading('Mark order as processing?')
                ->modalDescription('Use this when the printer has begun production.')
                ->action(function (): void {
                    app(MarkOrderProcessingAction::class)->execute($this->record);
                    Notification::make()->title('Order marked processing')->success()->send();
                })
                ->after(fn () => $this->refresh()),

            // processing → shipped (admin marks shipped after printer hands off)
            Action::make('markShipped')
                ->label('Mark shipped')
                ->color('info')
                ->icon('heroicon-o-truck')
                ->visible(fn (): bool => $this->record->status === 'processing')
                ->requiresConfirmation()
                ->modalHeading('Mark order as shipped?')
                ->modalDescription('The customer will be notified that their order is on the way.')
                ->action(function (): void {
                    app(MarkOrderShippedAction::class)->execute($this->record);
                    Notification::make()->title('Order marked shipped')->success()->send();
                })
                ->after(fn () => $this->refresh()),

            // shipped → delivered (admin confirms customer received it)
            Action::make('markDelivered')
                ->label('Mark delivered')
                ->color('success')
                ->icon('heroicon-o-check-badge')
                ->visible(fn (): bool => $this->record->status === 'shipped')
                ->requiresConfirmation()
                ->modalHeading('Mark order as delivered?')
                ->modalDescription('Confirms the customer received their order and notifies them.')
                ->action(function (): void {
                    app(MarkOrderDeliveredAction::class)->execute($this->record);
                    Notification::make()->title('Order marked delivered')->success()->send();
                })
                ->after(fn () => $this->refresh()),

            // cancel at any pre-delivered stage
            Action::make('cancel')
                ->label('Cancel order')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->visible(fn (): bool => ! in_array($this->record->status, ['cancelled', 'delivered'], true))
                ->requiresConfirmation()
                ->modalHeading('Cancel this order?')
                ->action(function (): void {
                    app(CancelOrderAction::class)->execute($this->record);
                    Notification::make()->title('Order cancelled')->success()->send();
                })
                ->after(fn () => $this->refresh()),
        ];
    }
}
