<?php

namespace Tests\Unit;

use App\Support\PhoneNumber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PhoneNumberTest extends TestCase
{
    #[DataProvider('validNumbers')]
    public function test_normalizes_indonesian_numbers_to_62_format(string $input, string $expected): void
    {
        $this->assertSame($expected, PhoneNumber::normalize($input));
    }

    public static function validNumbers(): array
    {
        return [
            '08xx' => ['081234567890', '6281234567890'],
            '62xx' => ['6281234567890', '6281234567890'],
            '+62xx' => ['+6281234567890', '6281234567890'],
            'with separators' => ['0812-3456 7890', '6281234567890'],
            'with parentheses' => ['(0812) 3456.7890', '6281234567890'],
            'without leading zero' => ['81234567890', '6281234567890'],
        ];
    }

    #[DataProvider('invalidNumbers')]
    public function test_rejects_invalid_numbers(?string $input): void
    {
        $this->assertNull(PhoneNumber::normalize($input));
        $this->assertFalse(PhoneNumber::isValid($input));
    }

    public static function invalidNumbers(): array
    {
        return [
            'null' => [null],
            'empty' => [''],
            'letters' => ['08abc'],
            'landline' => ['0215551234'],
            'too short' => ['0812345'],
            'too long' => ['0812345678901234'],
            'plus in middle' => ['08+1234567890'],
        ];
    }
}
