<?php

declare(strict_types=1);

namespace App\Filament\Resources\DeliveryCompanies\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DeliveryCompaniesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('coverage_zones')
                    ->label('Zones')
                    ->formatStateUsing(fn ($state): string => is_array($state)
                        ? implode(', ', $state)
                        : (string) ($state ?? ''))
                    ->placeholder('-')
                    ->wrap(),
                TextColumn::make('tracking_url_pattern')
                    ->label('Tracking URL')
                    ->placeholder('-')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('shipments_count')
                    ->counts('shipments')
                    ->label('Shipments')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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
