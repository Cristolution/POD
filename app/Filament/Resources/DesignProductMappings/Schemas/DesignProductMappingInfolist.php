<?php

declare(strict_types=1);

namespace App\Filament\Resources\DesignProductMappings\Schemas;

use App\Models\DesignProductMapping;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class DesignProductMappingInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('id')
                    ->label('ID'),
                TextEntry::make('design.title')
                    ->label('Design')
                    ->placeholder('-'),
                TextEntry::make('productTemplate.name')
                    ->label('Template')
                    ->placeholder('-'),
                TextEntry::make('preferredPrinter.company_name')
                    ->label('Preferred printer')
                    ->placeholder('-'),
                TextEntry::make('final_price')
                    ->money(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (DesignProductMapping $record): bool => $record->trashed()),
            ]);
    }
}
