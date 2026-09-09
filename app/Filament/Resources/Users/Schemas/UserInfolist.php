<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identity')
                    ->description('The public-facing profile for this account.')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name')
                            ->weight('bold'),
                        TextEntry::make('email')
                            ->label('Email address')
                            ->copyable()
                            ->placeholder('-'),
                    ]),

                Section::make('Access')
                    ->description('Role determines admin-panel reach; phone is used for fulfilment notifications.')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('role')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'admin' => 'danger',
                                'designer' => 'warning',
                                'printer_provider' => 'info',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'admin' => 'Admin',
                                'designer' => 'Designer',
                                'printer_provider' => 'Printer',
                                default => ucfirst($state),
                            }),
                        TextEntry::make('phone')
                            ->placeholder('-')
                            ->copyable(),
                    ]),

                Section::make('Timestamps')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('created_at')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('updated_at')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('deleted_at')
                            ->label('Deleted at')
                            ->dateTime()
                            ->placeholder('Active')
                            ->visible(fn (User $record): bool => $record->trashed())
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
