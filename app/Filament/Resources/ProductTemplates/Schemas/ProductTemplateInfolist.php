<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductTemplates\Schemas;

use App\Models\ProductTemplate;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ProductTemplateInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('id')
                    ->label('ID'),
                TextEntry::make('name'),
                TextEntry::make('type'),
                TextEntry::make('printerProvider.company_name')
                    ->label('Printer')
                    ->placeholder('-'),
                TextEntry::make('base_cost')
                    ->money(),
                TextEntry::make('specs')
                    ->placeholder('-')
                    ->columnSpanFull()
                    ->formatStateUsing(fn ($state): string => is_array($state)
                        ? collect($state)->map(fn ($v, $k) => "{$k}: {$v}")->implode("\n")
                        : (string) ($state ?? '-')),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (ProductTemplate $record): bool => $record->trashed()),
            ]);
    }
}
