<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductVariants\Schemas;

use App\Models\ProductVariant;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Detail-page infolist for a {@see ProductVariant}.
 *
 * Six sections:
 *   1. Identity      — UUID, SKU, parent template
 *   2. Attributes    — JSON `attributes` rendered as key/value lines
 *   3. Pricing       — price_delta and effective price (= base + delta)
 *   4. Availability  — is_active boolean
 *   5. Usage         — cart items / order items in flight
 *   6. Lifecycle     — created / updated / deleted
 */
class ProductVariantInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identity')
                    ->description('SKU, parent template, and identifier.')
                    ->icon(Heroicon::OutlinedSquaresPlus)
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('sku')
                                ->label('SKU')
                                ->placeholder('— no SKU —')
                                ->weight('bold')
                                ->icon(Heroicon::OutlinedQrCode)
                                ->copyable()
                                ->columnSpan(1),
                            TextEntry::make('productTemplate.type')
                                ->label('Product template')
                                ->placeholder('— orphaned —')
                                ->icon(Heroicon::OutlinedCube)
                                ->columnSpan(1),
                        ]),
                        TextEntry::make('id')
                            ->label('Variant ID')
                            ->icon(Heroicon::OutlinedHashtag)
                            ->copyable(),
                    ]),

                Section::make('Attributes')
                    ->description('Variant option bag (size, colour, material, …).')
                    ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
                    ->collapsible()
                    ->schema([
                        TextEntry::make('attributes')
                            ->label('')
                            ->placeholder('— no attributes —')
                            ->state(function (ProductVariant $record): string {
                                $bag = $record->attributes;
                                if (! is_array($bag) || $bag === []) {
                                    return '— no attributes —';
                                }

                                $lines = [];
                                foreach ($bag as $key => $value) {
                                    $valueLabel = is_array($value) ? json_encode($value) : (string) $value;
                                    $lines[] = "{$key}: {$valueLabel}";
                                }

                                return implode("\n", $lines);
                            })
                            ->columnSpanFull(),
                        TextEntry::make('attributes_summary')
                            ->label('Display label')
                            ->state(fn (ProductVariant $record): string => $record->label() ?: '—')
                            ->icon(Heroicon::OutlinedEye)
                            ->placeholder('— empty —'),
                    ]),

                Section::make('Pricing')
                    ->description('Adjustment over the template base cost.')
                    ->icon(Heroicon::OutlinedCurrencyDollar)
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('price_delta')
                                ->label('Price delta')
                                ->money('USD')
                                ->placeholder('$0.00')
                                ->icon(Heroicon::OutlinedCalculator),
                            TextEntry::make('effective_price')
                                ->label('Effective price')
                                ->state(function (ProductVariant $record): string {
                                    $template = $record->productTemplate;

                                    return '$'.number_format(
                                        (float) ($template?->base_cost ?? 0) + (float) $record->price_delta,
                                        2,
                                    );
                                })
                                ->placeholder('—')
                                ->weight('bold')
                                ->icon(Heroicon::OutlinedReceiptPercent),
                        ]),
                    ]),

                Section::make('Availability')
                    ->description('Whether customers can choose this variant.')
                    ->icon(Heroicon::OutlinedCheckBadge)
                    ->schema([
                        IconEntry::make('is_active')
                            ->label('Active for sale')
                            ->boolean()
                            ->trueIcon(Heroicon::OutlinedCheckCircle)
                            ->falseIcon(Heroicon::OutlinedXCircle)
                            ->trueColor('success')
                            ->falseColor('danger'),
                    ]),

                Section::make('Usage')
                    ->description('Where this variant is currently in use.')
                    ->icon(Heroicon::OutlinedShoppingCart)
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('cart_items')
                                ->label('In shopping carts')
                                ->formatStateUsing(fn ($state, ProductVariant $record): string => number_format($record->cartItems()->count()).' items')
                                ->icon(Heroicon::OutlinedShoppingCart),
                            TextEntry::make('order_items')
                                ->label('In orders')
                                ->formatStateUsing(fn ($state, ProductVariant $record): string => number_format($record->orderItems()->count()).' items')
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
                                ->visible(fn (ProductVariant $record): bool => $record->trashed()),
                        ]),
                    ]),
            ]);
    }
}
