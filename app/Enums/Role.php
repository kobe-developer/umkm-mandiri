<?php

namespace App\Enums;

enum Role: string
{
    case Owner = 'owner';
    case Cashier = 'kasir';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::Cashier => 'Kasir',
        };
    }
}
