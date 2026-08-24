<?php

declare(strict_types=1);

namespace App\Filament\Resources\OrderItems\Schemas;

use App\Models\OrderItem;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderItemInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Line item')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('id')
                            ->label('Order item ID'),
                        TextEntry::make('order.id')
                            ->label('Order')
                            ->placeholder('-'),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'pending' => 'gray',
                                'received' => 'info',
                                'printing' => 'warning',
                                'printed' => 'primary',
                                'handed_off' => 'success',
                                'cancelled' => 'danger',
                                default => 'gray',
                            }),
                        TextEntry::make('quantity')
                            ->numeric(),
                        TextEntry::make('unit_price')
                            ->money(),
                        TextEntry::make('line_total')
                            ->label('Line total')
                            ->money()
                            ->getStateUsing(fn (OrderItem $record): float => $record->lineTotal()),
                    ]),
                Section::make('References')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('designProductMapping.design.title')
                            ->label('Design')
                            ->placeholder('-'),
                        TextEntry::make('productVariant.sku')
                            ->label('Variant SKU')
                            ->placeholder('-'),
                        TextEntry::make('printerProvider.company_name')
                            ->label('Printer')
                            ->placeholder('-'),
                        TextEntry::make('created_at')
                            ->dateTime(),
                    ]),
                Section::make('Timestamps')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('updated_at')
                            ->dateTime(),
                        TextEntry::make('deleted_at')
                            ->dateTime()
                            ->placeholder('Active')
                            ->visible(fn (OrderItem $record): bool => $record->trashed()),
                    ]),
            ]);
    }
}
