<?php

declare(strict_types=1);

namespace App\Filament\Resources\Payments\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PaymentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Payment')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('id')
                            ->label('Payment ID')
                            ->copyable(),
                        TextEntry::make('order.id')
                            ->label('Order')
                            ->placeholder('-'),
                        TextEntry::make('method')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'cash_on_delivery' => 'Cash on delivery',
                                'bank_transfer' => 'Bank transfer',
                                'card' => 'Card',
                                default => $state,
                            }),
                        TextEntry::make('status')
                            ->badge(),
                        TextEntry::make('confirmedByAdmin.name')
                            ->label('Confirmed by')
                            ->placeholder('-'),
                        TextEntry::make('confirmed_at')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('created_at')
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->dateTime(),
                    ]),
            ]);
    }
}
