<?php

declare(strict_types=1);

namespace App\Filament\Resources\DesignProductMappings\Schemas;

use App\Models\DesignProductMapping;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Detail-page infolist for a {@see DesignProductMapping}.
 *
 * Five sections:
 *   1. Identity     — UUID + linked design / template
 *   2. Fulfillment  — preferred printer
 *   3. Pricing      — designer-set final price
 *   4. Usage        — carts and orders this mapping is in
 *   5. Lifecycle    — created / updated / deleted
 */
class DesignProductMappingInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identity')
                    ->description('Design + product pairing and identifier.')
                    ->icon(Heroicon::OutlinedLink)
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('design.title')
                                ->label('Design')
                                ->placeholder('— orphan —')
                                ->weight('bold')
                                ->icon(Heroicon::OutlinedPaintBrush)
                                ->columnSpan(1),
                            TextEntry::make('productTemplate.name')
                                ->label('Product template')
                                ->placeholder('— orphan —')
                                ->icon(Heroicon::OutlinedCube)
                                ->columnSpan(1),
                        ]),
                        TextEntry::make('id')
                            ->label('Mapping ID')
                            ->icon(Heroicon::OutlinedHashtag)
                            ->copyable(),
                    ]),

                Section::make('Fulfillment')
                    ->description('Designer-preferred printer for this pairing.')
                    ->icon(Heroicon::OutlinedTruck)
                    ->schema([
                        TextEntry::make('preferredPrinter.company_name')
                            ->label('Preferred printer')
                            ->placeholder('— no preference set —')
                            ->icon(Heroicon::OutlinedBuildingOffice),
                    ]),

                Section::make('Pricing')
                    ->description('Final customer-facing price (designer-set).')
                    ->icon(Heroicon::OutlinedCurrencyDollar)
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('final_price')
                                ->label('Final price')
                                ->money('USD')
                                ->placeholder('— not priced —')
                                ->icon(Heroicon::OutlinedBanknotes),
                            TextEntry::make('base_cost_hint')
                                ->label('Template base cost')
                                ->state(function (DesignProductMapping $record): string {
                                    $base = (float) ($record->productTemplate?->base_cost ?? 0);

                                    return '$'.number_format($base, 2);
                                })
                                ->placeholder('—')
                                ->icon(Heroicon::OutlinedCalculator),
                        ]),
                    ]),

                Section::make('Usage')
                    ->description('Carts and orders currently holding this mapping.')
                    ->icon(Heroicon::OutlinedShoppingCart)
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('cart_items')
                                ->label('In shopping carts')
                                ->formatStateUsing(fn ($state, DesignProductMapping $record): string => number_format($record->cartItems()->count()).' items')
                                ->icon(Heroicon::OutlinedShoppingCart),
                            TextEntry::make('order_items')
                                ->label('In orders')
                                ->formatStateUsing(fn ($state, DesignProductMapping $record): string => number_format($record->orderItems()->count()).' items')
                                ->icon(Heroicon::OutlinedReceiptRefund),
                        ]),
                    ]),

                Section::make('Lifecycle')
                    ->description('Timestamps for creation, last edit, and soft-deletion.')
                    ->icon(Heroicon::OutlinedClock)
                    ->collapsible()
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('created_at')
                                ->label('Created')
                                ->dateTime()
                                ->placeholder('—')
                                ->icon(Heroicon::OutlinedCalendar),
                            TextEntry::make('updated_at')
                                ->label('Last updated')
                                ->dateTime()
                                ->placeholder('—')
                                ->icon(Heroicon::OutlinedPencil),
                            TextEntry::make('deleted_at')
                                ->label('Deleted at')
                                ->dateTime()
                                ->placeholder('—')
                                ->icon(Heroicon::OutlinedArchiveBox)
                                ->visible(fn (DesignProductMapping $record): bool => $record->trashed()),
                        ]),
                    ]),
            ]);
    }
}
