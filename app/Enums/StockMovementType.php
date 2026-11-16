<?php

namespace App\Enums;

enum StockMovementType: string
{
    case In = 'masuk';
    case Out = 'keluar';
    case Adjustment = 'penyesuaian';
    case Return = 'retur';

    public function label(): string
    {
        return match ($this) {
            self::In => 'Stok Masuk',
            self::Out => 'Keluar (Order)',
            self::Adjustment => 'Penyesuaian',
            self::Return => 'Retur (Batal/Void)',
        };
    }
}
