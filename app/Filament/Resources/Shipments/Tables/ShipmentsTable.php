<?php

declare(strict_types=1);

namespace App\Filament\Resources\Shipments\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ShipmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('order.id')
                    ->label('Order')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('printerProvider.company_name')
                    ->label('Printer')
                    ->searchable()
                    ->placeholder('-'),
                TextColumn::make('deliveryCompany.name')
                    ->label('Carrier')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tracking_number')
                    ->searchable()
                    ->placeholder('-')
                    ->copyable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'shipped' => 'primary',
                        'delivered' => 'success',
                        'returned' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('shipped_at')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('delivered_at')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'shipped' => 'Shipped',
                        'delivered' => 'Delivered',
                        'returned' => 'Returned',
                    ]),
                SelectFilter::make('delivery_company_id')
                    ->label('Carrier')
                    ->relationship('deliveryCompany', 'name'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
