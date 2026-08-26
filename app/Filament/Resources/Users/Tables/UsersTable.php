<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('role')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'admin' => 'danger',
                        'designer' => 'warning',
                        'printer_provider' => 'info',
                        default => 'gray',
                    }),
                IconColumn::make('designerProfile.is_verified')
                    ->label('Verified designer')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-circle')
                    ->placeholder('—')
                    ->state(fn (?User $record): ?bool => $record?->role === 'designer' ? $record->designerProfile?->is_verified : null),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->placeholder('Active')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->options([
                        'admin' => 'Admin',
                        'designer' => 'Designer',
                        'printer_provider' => 'Printer',
                        'customer' => 'Customer',
                    ]),
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('toggleVerified')
                    ->label('Toggle verified')
                    ->icon('heroicon-o-check-badge')
                    ->color('warning')
                    ->visible(fn (?User $record): bool => $record?->role === 'designer')
                    ->requiresConfirmation()
                    ->action(function (User $record): void {
                        $profile = $record->designerProfile;
                        if ($profile === null) {
                            return;
                        }
                        $profile->update(['is_verified' => ! $profile->is_verified]);
                    }),
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
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
