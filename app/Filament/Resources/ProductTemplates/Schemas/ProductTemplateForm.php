<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductTemplates\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Ownership & type')
                    ->columns(2)
                    ->schema([
                        Select::make('printer_provider_id')
                            ->label('Printer provider')
                            ->relationship('printerProvider', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->user?->name ?? $record->id)
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('type')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Free text — printer-managed (e.g. "mug", "tshirt").'),
                    ]),
                Section::make('Pricing')
                    ->columns(1)
                    ->schema([
                        TextInput::make('base_cost')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->minValue(0)
                            ->step(0.01),
                    ]),
                Section::make('Specs (key-value)')
                    ->description('Free-form product specifications stored as JSON.')
                    ->schema([
                        KeyValue::make('specs')
                            ->keyLabel('Property')
                            ->valueLabel('Value')
                            ->addActionLabel('Add spec')
                            ->reorderable(),
                    ]),
            ]);
    }
}
