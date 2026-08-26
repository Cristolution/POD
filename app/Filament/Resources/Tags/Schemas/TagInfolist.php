<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tags\Schemas;

use App\Models\Tag;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Detail-page infolist for a {@see Tag}.
 *
 * Three sections:
 *   1. Identity   — name + popularity summary
 *   2. Usage      — how many designs carry this tag, with a hint at top-10 status
 *   3. Lifecycle  — created / updated timestamps
 */
class TagInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identity')
                    ->description('Tag name and identifier.')
                    ->icon(Heroicon::OutlinedTag)
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('name')
                                ->weight('bold')
                                ->size('lg')
                                ->columnSpan(1),
                            TextEntry::make('id')
                                ->label('Tag ID')
                                ->icon(Heroicon::OutlinedHashtag)
                                ->columnSpan(1),
                        ]),
                    ]),

                Section::make('Usage')
                    ->description('How many designs in the catalogue use this tag.')
                    ->icon(Heroicon::OutlinedChartBar)
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('designs')
                                ->label('Tagged designs')
                                ->formatStateUsing(fn ($state, $record): string => number_format($record->designs()->count()).' designs')
                                ->icon(Heroicon::OutlinedPaintBrush),
                            TextEntry::make('popularity_hint')
                                ->label('Popularity')
                                ->state(fn ($record): string => match (true) {
                                    $record->designs()->count() >= 10 => '🌶  Hot — top tier',
                                    $record->designs()->count() >= 3 => '·  Active',
                                    $record->designs()->count() >= 1 => '·  Niche',
                                    default => '·  Unused',
                                })
                                ->icon(Heroicon::OutlinedSparkles),
                        ]),
                    ]),

                Section::make('Lifecycle')
                    ->description('When this tag was added and last touched.')
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
