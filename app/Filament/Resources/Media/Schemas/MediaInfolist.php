<?php

declare(strict_types=1);

namespace App\Filament\Resources\Media\Schemas;

use App\Filament\Resources\Media\MediaResource;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MediaInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columns(2)
                    ->schema([
                        TextEntry::make('model_type')
                            ->label('Owner type')
                            ->formatStateUsing(fn (string $state): string => MediaResource::ALLOWED_OWNER_TYPES[$state] ?? $state),
                        TextEntry::make('model_id')
                            ->label('Owner ID')
                            ->numeric(),
                        TextEntry::make('collection_name')
                            ->label('Collection')
                            ->badge(),
                        TextEntry::make('file_path')
                            ->label('Path')
                            ->copyable()
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
