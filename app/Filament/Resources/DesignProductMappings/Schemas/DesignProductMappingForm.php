<?php

declare(strict_types=1);

namespace App\Filament\Resources\DesignProductMappings\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DesignProductMappingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Pairing')
                    ->columns(2)
                    ->schema([
                        Select::make('design_id')
                            ->label('Design')
                            ->relationship('design', 'title')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('product_template_id')
                            ->label('Product template')
                            ->relationship('productTemplate', 'type')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('preferred_printer_id')
                            ->label('Preferred printer')
                            ->relationship('preferredPrinter', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->user?->name ?? $record->id)
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('Designer-preferred fulfiller; informational only.'),
                    ]),
                Section::make('Pricing')
                    ->columns(1)
                    ->schema([
                        TextInput::make('final_price')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->minValue(0)
                            ->step(0.01),
                    ]),
            ]);
    }
}
