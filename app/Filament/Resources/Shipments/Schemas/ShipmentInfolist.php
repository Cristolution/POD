<?php

declare(strict_types=1);

namespace App\Filament\Resources\Shipments\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ShipmentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Routing')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('order.id')
                            ->label('Order')
                            ->placeholder('-'),
                        TextEntry::make('printerProvider.company_name')
                            ->label('Printer')
                            ->placeholder('-'),
                        TextEntry::make('deliveryCompany.name')
                            ->label('Carrier')
                            ->placeholder('-'),
                        TextEntry::make('tracking_number')
                            ->placeholder('-')
                            ->copyable(),
                    ]),
                Section::make('Status')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('status')
                            ->badge(),
                        TextEntry::make('shipped_at')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('delivered_at')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('created_at')
                            ->dateTime(),
                    ]),
            ]);
    }
}
