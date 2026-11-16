<?php

namespace App\Support;

/**
 * Normalisasi nomor WhatsApp Indonesia ke format 62xxxxxxxxxx.
 * Menerima 08xx, 8xx, 62xx, +62xx (spasi, strip, titik, kurung diabaikan).
 */
final class PhoneNumber
{
    private const MIN_SUBSCRIBER_DIGITS = 8;

    private const MAX_SUBSCRIBER_DIGITS = 13;

    public static function normalize(?string $input): ?string
    {
        if ($input === null) {
            return null;
        }

        $digits = preg_replace('/[\s\-\.\(\)]/', '', trim($input));
        if ($digits === '' || ! preg_match('/^\+?\d+$/', $digits)) {
            return null;
        }

        $digits = ltrim($digits, '+');
        $subscriber = match (true) {
            str_starts_with($digits, '62') => substr($digits, 2),
            str_starts_with($digits, '0') => substr($digits, 1),
            default => $digits,
        };

        $length = strlen($subscriber);
        if (! str_starts_with($subscriber, '8') || $length < self::MIN_SUBSCRIBER_DIGITS || $length > self::MAX_SUBSCRIBER_DIGITS) {
            return null;
        }

        return '62'.$subscriber;
    }

    public static function isValid(?string $input): bool
    {
        return self::normalize($input) !== null;
    }
}
