<?php

namespace App\Enums;

/**
 * Alur: Baru → Dikonfirmasi → Diproses → Siap/Diantar → Selesai.
 * Order dine in & take away langsung masuk di Diproses.
 * Dibatalkan bisa dicapai dari status mana pun sebelum Selesai.
 */
enum OrderStatus: string
{
    case New = 'baru';
    case Confirmed = 'dikonfirmasi';
    case Processing = 'diproses';
    case Ready = 'siap';
    case Completed = 'selesai';
    case Cancelled = 'dibatalkan';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Baru',
            self::Confirmed => 'Dikonfirmasi',
            self::Processing => 'Diproses',
            self::Ready => 'Siap/Diantar',
            self::Completed => 'Selesai',
            self::Cancelled => 'Dibatalkan',
        };
    }

    public function next(): ?self
    {
        return match ($this) {
            self::New => self::Confirmed,
            self::Confirmed => self::Processing,
            self::Processing => self::Ready,
            self::Ready => self::Completed,
            self::Completed, self::Cancelled => null,
        };
    }

    public function isFinal(): bool
    {
        return $this === self::Completed || $this === self::Cancelled;
    }

    /** @return list<array{value: string, label: string}> */
    public static function options(): array
    {
        return array_map(fn (self $s) => ['value' => $s->value, 'label' => $s->label()], self::cases());
    }
}
