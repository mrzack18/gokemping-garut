<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Pemetaan unit bisnis ke prefix route Wayfinder atau route name Laravel.
 *
 * Route publik ditulis per unit (`/gokemping/...` dan `/sewa-sepeda-garut/...`)
 * lalu memakai controller yang sama dengan slug unit sebagai route default.
 * Kelas ini menyatukan pengetahuan "unit ini route-nya apa" supaya controller
 * tidak perlu menyusun nama route dengan string.
 */
final class BookingRoutes
{
    /**
     * Unit bisnis yang punya halaman booking publik.
     *
     * @var list<string>
     */
    public const SLUGS = ['gokemping', 'sewa-sepeda-garut'];

    /**
     * Prefix route Wayfinder untuk unit bisnis.
     */
    public static function prefix(string $slug): string
    {
        return match ($slug) {
            'gokemping' => 'gokemping',
            'sewa-sepeda-garut' => 'sewaSepedaGarut',
            default => throw new InvalidArgumentException(
                "Unit bisnis {$slug} tidak punya halaman booking.",
            ),
        };
    }

    public static function isKnown(string $slug): bool
    {
        return in_array($slug, self::SLUGS, true);
    }

    /**
     * Unit sewa sepeda punya field tambahan jumlah penyewa (PRD section 14).
     */
    public static function isBikeRental(string $slug): bool
    {
        return $slug === 'sewa-sepeda-garut';
    }
}
