<?php

declare(strict_types=1);

namespace App\Filament\Resources\Categories\Schemas;

use App\Models\Category;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Detail-page infolist for a {@see Category}.
 *
 * Layout — three sections that read top-to-bottom:
 *   1. Identity    — name + parent breadcrumb
 *   2. Catalogue   — direct-children and designs count
 *   3. Lifecycle   — created / updated timestamps
 */
class CategoryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identity')
                    ->description('Name and hierarchy position.')
                    ->icon(Heroicon::OutlinedSquares2x2)
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('name')
                                ->weight('bold')
                                ->size('lg')
                                ->columnSpan(1),
                            TextEntry::make('parent.name')
                                ->label('Parent category')
                                ->placeholder('— root category —')
                                ->icon(Heroicon::OutlinedChevronUp)
                                ->columnSpan(1),
                        ]),
                    ]),

                Section::make('Catalogue')
                    ->description('How this category is used across the catalogue.')
                    ->icon(Heroicon::OutlinedChartBar)
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('children')
                                ->label('Direct sub-categories')
                                ->formatStateUsing(fn ($state, $record): string => number_format($record->children()->count()).' sub-categories')
                                ->icon(Heroicon::OutlinedRectangleStack),
                            TextEntry::make('designs')
                                ->label('Designs in this category')
                                ->formatStateUsing(fn ($state, $record): string => number_format($record->designs()->count()).' designs')
                                ->icon(Heroicon::OutlinedPaintBrush),
                        ]),
                    ]),

                Section::make('Lifecycle')
                    ->description('When this category was added and last touched.')
                    ->icon(Heroicon::OutlinedClock)
                    ->collapsible()
                    ->schema([
                        Grid::make(2)->schema([
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
                        ]),
                    ]),
            ]);
    }
}
