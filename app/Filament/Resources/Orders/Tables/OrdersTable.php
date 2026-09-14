<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders\Tables;

use App\Actions\Order\MarkOrderDeliveredAction;
use App\Actions\Order\MarkOrderPaidAction;
use App\Actions\Order\MarkOrderProcessingAction;
use App\Actions\Order\MarkOrderShippedAction;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'paid' => 'info',
                        'processing' => 'warning',
                        'shipped' => 'primary',
                        'delivered' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('shipping_city')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('shipping_country')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('total_amount')
                    ->money()
                    ->sortable(),
                TextColumn::make('items_count')
                    ->counts('items')
                    ->label('Items')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'processing' => 'Processing',
                        'shipped' => 'Shipped',
                        'delivered' => 'Delivered',
                        'cancelled' => 'Cancelled',
                    ]),
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('advanceState')
                    ->label(fn ($record): string => match ($record->status) {
                        'pending' => 'Mark paid',
                        'paid' => 'Mark processing',
                        'processing' => 'Mark shipped',
                        'shipped' => 'Mark delivered',
                        default => 'Advance',
                    })
                    ->icon('heroicon-o-arrow-right-circle')
                    ->color('primary')
                    ->visible(fn ($record): bool => in_array($record->status, ['pending', 'paid', 'processing', 'shipped'], true))
                    ->requiresConfirmation()
                    ->modalHeading(fn ($record): string => "Advance order #{$record->id}?")
                    ->modalDescription(fn ($record): string => "Moves this order from '{$record->status}' to the next state and fires any customer notifications.")
                    ->action(function ($record): void {
                        $action = match ($record->status) {
                            'pending' => MarkOrderPaidAction::class,
                            'paid' => MarkOrderProcessingAction::class,
                            'processing' => MarkOrderShippedAction::class,
                            'shipped' => MarkOrderDeliveredAction::class,
                            default => null,
                        };

                        if ($action === null) {
                            Notification::make()
                                ->title('No transition available')
                                ->warning()
                                ->send();

                            return;
                        }

                        app($action)->execute($record);

                        Notification::make()
                            ->title("Order #{$record->id} advanced")
                            ->success()
                            ->send();
                    }),
                ViewAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ]);
    }
}
