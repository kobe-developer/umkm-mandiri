<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

/**
 * Pelanggaran aturan bisnis dikembalikan sebagai error validasi
 * agar tampil rapi di form Inertia.
 */
final class BusinessRule
{
    public static function fail(string $message, string $field = 'order'): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }

    public static function check(bool $condition, string $message, string $field = 'order'): void
    {
        if (! $condition) {
            self::fail($message, $field);
        }
    }
}
