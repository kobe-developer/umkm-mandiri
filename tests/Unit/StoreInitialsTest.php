<?php

namespace Tests\Unit;

use App\Support\StoreInitials;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class StoreInitialsTest extends TestCase
{
    /** @return array<string, array{0: ?string, 1: string}> */
    public static function names(): array
    {
        return [
            'dua kata' => ['UMKM Mandiri', 'UM'],
            'tiga kata' => ['Ayam Penyet Jomblo', 'AP'],
            'satu kata: huruf pertama + terakhir' => ['Juciuecy', 'JY'],
            'huruf kecil' => ['warung bu sri', 'WB'],
            'tanda baca diabaikan' => ['Kopi & Roti — Pak Joe', 'KR'],
            'spasi berlebih' => ['  Bakso   Solo ', 'BS'],
            'satu huruf' => ['X', 'X'],
            'kosong' => ['', 'UM'],
            'null' => [null, 'UM'],
        ];
    }

    #[DataProvider('names')]
    public function test_initials(?string $name, string $expected): void
    {
        $this->assertSame($expected, StoreInitials::from($name));
    }
}
