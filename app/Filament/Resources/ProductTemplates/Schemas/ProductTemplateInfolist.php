<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductTemplates\Schemas;

use App\Models\ProductTemplate;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Detail-page infolist for a {@see ProductTemplate}.
 *
 * Six sections:
 *   1. Identity   — UUID, name, type
 *   2. Printer    — fulfiller and company
 *   3. Pricing    — base cost
 *   4. Specs      — JSON `specs` rendered as key/value lines
 *   5. Usage      — variants, active variants, design mappings
 *   6. Lifecycle  — created / updated / deleted
 */
class ProductTemplateInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identity')
                    ->description('Name and product type.')
                    ->icon(Heroicon::OutlinedCube)
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('name')
                                ->weight('bold')
                                ->size('lg')
                                ->columnSpan(1),
                            TextEntry::make('type')
                                ->badge()
                                ->color('info')
                                ->icon(Heroicon::OutlinedSwatch)
                                ->placeholder('— unspecified —')
                                ->columnSpan(1),
                        ]),
                        TextEntry::make('id')
                            ->label('Template ID')
                            ->icon(Heroicon::OutlinedHashtag)
                            ->copyable(),
                    ]),

                Section::make('Printer')
                    ->description('Fulfiller who produces this template.')
                    ->icon(Heroicon::OutlinedPrinter)
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('printerProvider.company_name')
                                ->label('Company')
                                ->placeholder('— no printer assigned —')
                                ->icon(Heroicon::OutlinedBuildingOffice),
                            TextEntry::make('printerProvider.user.name')
                                ->label('Owner')
                                ->placeholder('—')
                                ->icon(Heroicon::OutlinedUser),
                        ]),
                    ]),

                Section::make('Pricing')
                    ->description('Base price before any variant adjustments.')
                    ->icon(Heroicon::OutlinedCurrencyDollar)
                    ->schema([
                        TextEntry::make('base_cost')
                            ->label('Base cost')
                            ->money('USD')
                            ->placeholder('— not priced —')
                            ->icon(Heroicon::OutlinedBanknotes),
                    ]),

                Section::make('Specs')
                    ->description('Structural / production specifications (JSON).')
                    ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
                    ->collapsible()
                    ->schema([
                        TextEntry::make('specs')
                            ->label('')
                            ->placeholder('— no specs recorded —')
                            ->state(fn (ProductTemplate $record): string => $record->specs !== null && $record->specs !== []
                                ? collect($record->specs)
                                    ->map(fn ($v, $k) => "{$k}: {$v}")
                                    ->implode("\n")
                                : '— no specs recorded —')
                            ->columnSpanFull(),
                    ]),

                Section::make('Usage')
                    ->description('How many variants and designs are linked to this template.')
                    ->icon(Heroicon::OutlinedChartBar)
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('variants')
                                ->label('Total variants')
                                ->formatStateUsing(fn ($state, ProductTemplate $record): string => number_format($record->variants()->count()).' variants')
                                ->icon(Heroicon::OutlinedSquaresPlus),
                            TextEntry::make('active_variants')
                                ->label('Active variants')
                                ->formatStateUsing(fn ($state, ProductTemplate $record): string => number_format($record->activeVariants()->count()).' active')
                                ->icon(Heroicon::OutlinedCheckCircle),
                            TextEntry::make('mappings')
                                ->label('Design mappings')
                                ->formatStateUsing(fn ($state, ProductTemplate $record): string => number_format($record->designProductMappings()->count()).' mappings')
                                ->icon(Heroicon::OutlinedLink),
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
                                ->visible(fn (ProductTemplate $record): bool => $record->trashed()),
                        ]),
                    ]),
            ]);
    }
}
