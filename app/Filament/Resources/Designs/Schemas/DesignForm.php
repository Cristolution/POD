<?php

declare(strict_types=1);

namespace App\Filament\Resources\Designs\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DesignForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Ownership')
                    ->columns(2)
                    ->schema([
                        Select::make('designer_id')
                            ->label('Designer')
                            ->relationship('designer', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->user?->name ?? $record->id)
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('Designer profile whose owner is the user shown.'),
                        Select::make('category_id')
                            ->label('Category')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                    ]),
                Section::make('Catalog data')
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        Select::make('status')
                            ->required()
                            ->options([
                                'draft' => 'Draft',
                                'published' => 'Published',
                                'archived' => 'Archived',
                            ])
                            ->default('draft'),
                    ]),
                Section::make('Tags')
                    ->schema([
                        Select::make('tags')
                            ->label('Tags')
                            ->relationship('tags', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->helperText('Pick or create tags. Pivots via the design_tag table.'),
                    ]),
            ]);
    }
}
