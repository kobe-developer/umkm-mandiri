<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Unpaid = 'belum_bayar';
    case Paid = 'lunas';
    case Void = 'void';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'Belum Bayar',
            self::Paid => 'Lunas',
            self::Void => 'Void',
        };
    }
}
