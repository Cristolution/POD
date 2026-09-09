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
                Section::make('Profile')
                    ->description('Identity and contact details for this account.')
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
                            ->disabled(fn ($record) => $record?->id === auth()->id())
                            ->helperText(fn ($record): ?string => $record?->id === auth()->id()
                                ? 'You cannot change your own role.'
                                : null),
                    ]),
                Section::make('Security')
                    ->description('Set or rotate the account password. Leave blank to keep the current password.')
                    ->columns(1)
                    ->schema([
                        TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->minLength(8)
                            ->confirmed()
                            ->autocomplete('new-password'),
                        TextInput::make('password_confirmation')
                            ->label('Confirm password')
                            ->password()
                            ->revealable()
                            ->dehydrated(false)
                            ->requiredWith('password')
                            ->minLength(8)
                            ->autocomplete('new-password'),
                    ]),
            ]);
    }
}
