<?php

declare(strict_types=1);

namespace App\Filament\Resources\Designs\Schemas;

use App\Models\Design;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Detail-page infolist for a {@see Design}.
 *
 * Five sections:
 *   1. Identity    — title, status badge, UUID
 *   2. Attribution — designer + category
 *   3. Catalogue   — tags list, published/total mapping counts, media counts
 *   4. Lifecycle   — created / updated / deleted (when trashed)
 */
class DesignInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identity')
                    ->description('Title and current publication state.')
                    ->icon(Heroicon::OutlinedPaintBrush)
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('title')
                                ->weight('bold')
                                ->size('lg')
                                ->columnSpan(1),
                            TextEntry::make('status')
                                ->badge()
                                ->formatStateUsing(fn (string $state): string => match ($state) {
                                    'draft' => 'Draft',
                                    'published' => 'Published',
                                    'archived' => 'Archived',
                                    default => ucfirst($state),
                                })
                                ->color(fn (string $state): string => match ($state) {
                                    'published' => 'success',
                                    'draft' => 'warning',
                                    'archived' => 'gray',
                                    default => 'gray',
                                })
                                ->icon(fn (string $state): Heroicon => match ($state) {
                                    'published' => Heroicon::OutlinedCheckCircle,
                                    'draft' => Heroicon::OutlinedPencilSquare,
                                    'archived' => Heroicon::OutlinedArchiveBox,
                                    default => Heroicon::OutlinedQuestionMarkCircle,
                                })
                                ->columnSpan(1),
                        ]),
                        TextEntry::make('id')
                            ->label('Design ID')
                            ->icon(Heroicon::OutlinedHashtag)
                            ->copyable(),
                    ]),

                Section::make('Attribution')
                    ->description('Who owns this design and where it lives in the tree.')
                    ->icon(Heroicon::OutlinedUserCircle)
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('designer.user.name')
                                ->label('Designer')
                                ->placeholder('— orphan —')
                                ->icon(Heroicon::OutlinedUser),
                            TextEntry::make('category.name')
                                ->label('Category')
                                ->placeholder('— uncategorised —')
                                ->icon(Heroicon::OutlinedSquares2x2),
                        ]),
                    ]),

                Section::make('Catalogue')
                    ->description('Tags and product mappings linked to this design.')
                    ->icon(Heroicon::OutlinedRectangleStack)
                    ->schema([
                        TextEntry::make('tags.name')
                            ->label('Tags')
                            ->placeholder('— no tags —')
                            ->state(fn (Design $record): string => $record->tags->pluck('name')->all() !== []
                                ? $record->tags->pluck('name')->implode(' · ')
                                : '— no tags —')
                            ->badge()
                            ->columnSpanFull(),
                        Grid::make(3)->schema([
                            TextEntry::make('mappings_count')
                                ->label('Total mappings')
                                ->formatStateUsing(fn ($state, Design $record): string => number_format($record->mappings()->count()).' mappings')
                                ->icon(Heroicon::OutlinedLink),
                            TextEntry::make('published_mappings')
                                ->label('Published mappings')
                                ->formatStateUsing(fn ($state, Design $record): string => number_format($record->publishedMappings()->count()).' live')
                                ->icon(Heroicon::OutlinedCheckBadge),
                            TextEntry::make('media_count')
                                ->label('Media files')
                                ->formatStateUsing(fn ($state, Design $record): string => number_format($record->media()->count()).' files')
                                ->icon(Heroicon::OutlinedPhoto),
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
                                ->label('Archived at')
                                ->dateTime()
                                ->placeholder('—')
                                ->icon(Heroicon::OutlinedArchiveBox)
                                ->visible(fn (Design $record): bool => $record->trashed()),
                        ]),
                    ]),
            ]);
    }
}
