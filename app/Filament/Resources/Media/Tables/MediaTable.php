<?php

declare(strict_types=1);

namespace App\Filament\Resources\Media\Tables;

use App\Filament\Resources\Media\MediaResource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MediaTable
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
                TextColumn::make('model_type')
                    ->label('Owner type')
                    ->formatStateUsing(fn (string $state): string => MediaResource::ALLOWED_OWNER_TYPES[$state] ?? $state)
                    ->badge()
                    ->sortable(),
                TextColumn::make('model_id')
                    ->label('Owner ID')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('collection_name')
                    ->label('Collection')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'mockup' => 'info',
                        'print_file' => 'primary',
                        'payment_proof' => 'warning',
                        'attachment' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('file_path')
                    ->label('Path')
                    ->searchable()
                    ->copyable()
                    ->wrap(),
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
                SelectFilter::make('model_type')
                    ->label('Owner type')
                    ->options(MediaResource::ALLOWED_OWNER_TYPES),
                SelectFilter::make('collection_name')
                    ->label('Collection')
                    ->options([
                        'mockup' => 'Mockup',
                        'print_file' => 'Print file',
                        'payment_proof' => 'Payment proof',
                        'attachment' => 'Attachment',
                    ]),
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
