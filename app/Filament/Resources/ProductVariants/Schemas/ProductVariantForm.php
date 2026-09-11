<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductVariants\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductVariantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Template')
                    ->columns(2)
                    ->schema([
                        Select::make('product_template_id')
                            ->label('Product template')
                            ->relationship('productTemplate', 'type')
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('sku')
                            ->label('SKU')
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                    ]),
                Section::make('Attributes (key-value)')
                    ->description('E.g. size=L, color=black. Stored as JSON.')
                    ->schema([
                        KeyValue::make('attributes')
                            ->required()
                            ->keyLabel('Property')
                            ->valueLabel('Value')
                            ->addActionLabel('Add attribute')
                            ->reorderable(),
                    ]),
                Section::make('Pricing & availability')
                    ->columns(2)
                    ->schema([
                        TextInput::make('price_delta')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->default(0)
                            ->step(0.01),
                        Toggle::make('is_active')
                            ->required()
                            ->default(true),
                    ]),
            ]);
    }
}
