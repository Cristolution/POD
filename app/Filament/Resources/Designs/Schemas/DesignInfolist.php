<?php

declare(strict_types=1);

namespace App\Filament\Resources\Designs\Schemas;

use App\Models\Design;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class DesignInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('id')
                    ->label('ID'),
                TextEntry::make('title'),
                TextEntry::make('designer.user.name')
                    ->label('Designer')
                    ->placeholder('-'),
                TextEntry::make('category.name')
                    ->label('Category')
                    ->placeholder('-'),
                TextEntry::make('status')
                    ->badge(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (Design $record): bool => $record->trashed()),
            ]);
    }
}
