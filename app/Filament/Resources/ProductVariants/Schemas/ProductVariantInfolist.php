<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductVariants\Schemas;

use App\Models\ProductVariant;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ProductVariantInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('id')
                    ->label('ID'),
                TextEntry::make('productTemplate.name')
                    ->label('Product template')
                    ->placeholder('-'),
                TextEntry::make('sku')
                    ->label('SKU')
                    ->placeholder('-'),
                TextEntry::make('attributes')
                    ->placeholder('-')
                    ->columnSpanFull()
                    ->formatStateUsing(fn ($state): string => is_array($state)
                        ? collect($state)->map(fn ($v, $k) => "{$k}: {$v}")->implode("\n")
                        : (string) ($state ?? '-')),
                TextEntry::make('price_delta')
                    ->money(),
                IconEntry::make('is_active')
                    ->boolean(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (ProductVariant $record): bool => $record->trashed()),
            ]);
    }
}
