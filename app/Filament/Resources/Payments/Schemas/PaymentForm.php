<?php

declare(strict_types=1);

namespace App\Filament\Resources\Payments\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PaymentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Order & method')
                    ->columns(2)
                    ->schema([
                        Select::make('order_id')
                            ->label('Order')
                            ->relationship('order', 'id')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('method')
                            ->required()
                            ->options([
                                'cash_on_delivery' => 'Cash on delivery',
                                'bank_transfer' => 'Bank transfer',
                                'card' => 'Card',
                            ]),
                    ]),
                Section::make('Confirmation')
                    ->description('Set by the admin who confirms the payment.')
                    ->columns(2)
                    ->schema([
                        Select::make('status')
                            ->required()
                            ->options([
                                'pending' => 'Pending',
                                'confirmed' => 'Confirmed',
                                'rejected' => 'Rejected',
                            ])
                            ->default('pending'),
                        Select::make('confirmed_by_admin_id')
                            ->label('Confirmed by admin')
                            ->relationship('confirmedByAdmin', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder('-'),
                        DateTimePicker::make('confirmed_at')
                            ->label('Confirmed at'),
                    ]),
            ]);
    }
}
