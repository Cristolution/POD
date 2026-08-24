<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(120),
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true),
                        TextInput::make('phone')
                            ->tel()
                            ->maxLength(32),
                        Select::make('role')
                            ->required()
                            ->options([
                                'admin' => 'Admin',
                                'designer' => 'Designer',
                                'printer_provider' => 'Printer',
                                'customer' => 'Customer',
                            ])
                            ->disabled(fn ($record) => $record?->id === auth()->id()),
                        TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->minLength(8),
                    ]),
            ]);
    }
}
