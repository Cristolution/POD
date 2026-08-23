<?php

declare(strict_types=1);

namespace App\Support;

enum TokenAbility: string
{
    case Customer = 'customer';
    case Designer = 'designer';
    case Printer = 'printer';
    case Admin = 'admin';

    /** @return array<int,string> */
    public static function forRole(string $role): array
    {
        return match ($role) {
            'admin' => [self::Admin->value, self::Customer->value],
            'designer' => [self::Designer->value, self::Customer->value],
            'printer_provider' => [self::Printer->value, self::Customer->value],
            default => [self::Customer->value],
        };
    }
}
