<?php

declare(strict_types=1);

namespace App\Filament\Resources\DeliveryCompanies\Schemas;

use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DeliveryCompanyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TagsInput::make('coverage_zones')
                            ->label('Coverage zones')
                            ->placeholder('Add a zone (e.g. EU, US, CA)')
                            ->helperText('Regions the delivery company serves. Stored as a JSON array.')
                            ->columnSpanFull(),
                        TextInput::make('tracking_url_pattern')
                            ->label('Tracking URL pattern')
                            ->placeholder('https://track.example.com/{number}')
                            ->helperText('Use {number} as the placeholder for the tracking number.')
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
