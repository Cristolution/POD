<?php

declare(strict_types=1);

namespace App\Filament\Resources\Media\Schemas;

use App\Filament\Resources\Media\MediaResource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MediaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Owner')
                    ->columns(2)
                    ->schema([
                        Select::make('model_type')
                            ->label('Owner type')
                            ->options(MediaResource::ALLOWED_OWNER_TYPES)
                            ->required()
                            ->live()
                            ->helperText('Polymorphic owner class.'),
                        TextInput::make('model_id')
                            ->label('Owner ID')
                            ->required()
                            ->helperText('Primary key of the owner record.'),
                    ]),
                Section::make('File')
                    ->columns(2)
                    ->schema([
                        Select::make('collection_name')
                            ->label('Collection')
                            ->required()
                            ->options([
                                'mockup' => 'Mockup',
                                'print_file' => 'Print file',
                                'payment_proof' => 'Payment proof',
                                'attachment' => 'Attachment',
                            ]),
                        TextInput::make('file_path')
                            ->label('File path')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
