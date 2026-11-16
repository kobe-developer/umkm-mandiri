<?php

namespace App\Enums;

enum OrderChannel: string
{
    case DineIn = 'dine_in';
    case TakeAway = 'take_away';
    case Online = 'online';

    public function label(): string
    {
        return match ($this) {
            self::DineIn => 'Dine In',
            self::TakeAway => 'Take Away',
            self::Online => 'Online',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
