<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders\Schemas;

use App\Models\Order;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Order summary')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('id')
                            ->label('Order ID')
                            ->copyable(),
                        TextEntry::make('customer.name')
                            ->label('Customer')
                            ->placeholder('-'),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'pending' => 'gray',
                                'paid' => 'info',
                                'processing' => 'warning',
                                'shipped' => 'primary',
                                'delivered' => 'success',
                                'cancelled' => 'danger',
                                default => 'gray',
                            }),
                        TextEntry::make('total_amount')
                            ->money(),
                        TextEntry::make('created_at')
                            ->label('Placed at')
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->dateTime(),
                    ]),
                Section::make('Shipping snapshot')
                    ->description('Captured at checkout — preserved even if the address book changes.')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('shipping_line1'),
                        TextEntry::make('shipping_city'),
                        TextEntry::make('shipping_country'),
                        TextEntry::make('shipping_phone')
                            ->placeholder('-'),
                    ]),
                Section::make('Timestamps')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('deleted_at')
                            ->dateTime()
                            ->placeholder('Active')
                            ->visible(fn (Order $record): bool => $record->trashed()),
                    ]),
            ]);
    }
}
