<?php

namespace App\Support;

/**
 * Inisial monogram toko (favicon, ikon notifikasi, logo di aplikasi):
 * - dua kata atau lebih → huruf pertama dua kata pertama ("Ayam Penyet Jomblo" → AP)
 * - satu kata           → huruf pertama + terakhir ("Juciuecy" → JY)
 * Tanda baca diabaikan. Logika yang sama ada di resources/js/components/Logo.svelte.
 */
final class StoreInitials
{
    private const FALLBACK = 'UM';

    public static function from(?string $name): string
    {
        $words = preg_split('/\s+/u', trim(preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', (string) $name)), -1, PREG_SPLIT_NO_EMPTY);

        if ($words === false || $words === []) {
            return self::FALLBACK;
        }

        $initials = count($words) >= 2
            ? mb_substr($words[0], 0, 1).mb_substr($words[1], 0, 1)
            : mb_substr($words[0], 0, 1).(mb_strlen($words[0]) > 1 ? mb_substr($words[0], -1) : '');

        return mb_strtoupper($initials);
    }
}
