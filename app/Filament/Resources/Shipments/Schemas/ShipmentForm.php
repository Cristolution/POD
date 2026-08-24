<?php

declare(strict_types=1);

namespace App\Filament\Resources\Shipments\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ShipmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Routing')
                    ->columns(2)
                    ->schema([
                        Select::make('order_id')
                            ->label('Order')
                            ->relationship('order', 'id')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('printer_provider_id')
                            ->label('Printer provider')
                            ->relationship('printerProvider', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->user?->name ?? $record->id)
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('delivery_company_id')
                            ->label('Delivery company')
                            ->relationship('deliveryCompany', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                    ]),
                Section::make('Tracking & status')
                    ->columns(2)
                    ->schema([
                        TextInput::make('tracking_number')
                            ->maxLength(255),
                        Select::make('status')
                            ->required()
                            ->options([
                                'pending' => 'Pending',
                                'shipped' => 'Shipped',
                                'delivered' => 'Delivered',
                                'returned' => 'Returned',
                            ])
                            ->default('pending'),
                        DateTimePicker::make('shipped_at')
                            ->label('Shipped at'),
                        DateTimePicker::make('delivered_at')
                            ->label('Delivered at'),
                    ]),
            ]);
    }
}
