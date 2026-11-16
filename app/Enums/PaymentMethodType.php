<?php

namespace App\Enums;

enum PaymentMethodType: string
{
    case Cash = 'tunai';
    case Qris = 'qris';
    case Transfer = 'transfer';
    case Ewallet = 'ewallet';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Tunai',
            self::Qris => 'QRIS',
            self::Transfer => 'Transfer Bank',
            self::Ewallet => 'E-Wallet',
        };
    }

    /** @return list<array{value: string, label: string}> */
    public static function options(): array
    {
        return array_map(fn (self $t) => ['value' => $t->value, 'label' => $t->label()], self::cases());
    }
}
