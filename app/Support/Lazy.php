<?php

namespace App\Support;

use Closure;

/**
 * Nilai yang baru dihitung saat pertama dibutuhkan, lalu dipakai ulang.
 *
 * Dipakai bersama props Inertia yang lazy (closure): partial reload yang tidak meminta
 * prop tersebut tidak menjalankan query-nya sama sekali, sementara beberapa prop yang
 * berbagi data (mis. setting toko) tetap hanya memicu satu query pada muat penuh.
 */
final class Lazy
{
    /**
     * @template T
     *
     * @param  Closure(): T  $resolver
     * @return Closure(): T
     */
    public static function memo(Closure $resolver): Closure
    {
        $resolved = false;
        $value = null;

        return function () use ($resolver, &$resolved, &$value) {
            if (! $resolved) {
                $value = $resolver();
                $resolved = true;
            }

            return $value;
        };
    }
}
