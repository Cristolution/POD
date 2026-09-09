<?php

declare(strict_types=1);

namespace App\Filament\Resources\Addresses\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AddressInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Owner')
                    ->description('The customer account this address book entry belongs to.')
                    ->schema([
                        TextEntry::make('user.name')
                            ->label('User')
                            ->placeholder('-'),
                    ]),

                Section::make('Address')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('line1')
                            ->label('Street address')
                            ->columnSpanFull(),
                        TextEntry::make('city')
                            ->placeholder('-'),
                        TextEntry::make('country')
                            ->placeholder('-'),
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
                    ]),
            ]);
    }
}
