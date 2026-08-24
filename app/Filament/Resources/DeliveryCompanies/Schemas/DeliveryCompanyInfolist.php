<?php

declare(strict_types=1);

namespace App\Filament\Resources\DeliveryCompanies\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DeliveryCompanyInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('coverage_zones')
                            ->label('Coverage zones')
                            ->placeholder('-')
                            ->formatStateUsing(fn ($state): string => is_array($state)
                                ? implode(', ', $state)
                                : (string) ($state ?? '-')),
                        TextEntry::make('tracking_url_pattern')
                            ->label('Tracking URL pattern')
                            ->placeholder('-')
                            ->columnSpanFull(),
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
