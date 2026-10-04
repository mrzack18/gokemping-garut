<?php

namespace App\Support;

/**
 * Normalisasi nomor WhatsApp ke format penyimpanan `62xxxxxxxxxx`.
 *
 * `customers.whatsapp` punya unique constraint, jadi format harus konsisten
 * supaya orang yang sama tidak terpecah jadi beberapa baris. Penyewa mengetik
 * dengan berbagai bentuk: `+62 812-3456-7890`, `081234567890`, atau
 * `6281234567890`. Semuanya dinormalkan ke `6281234567890`.
 */
final class WhatsappNumber
{
    /**
     * Panjang digit lokal setelah `62` untuk nomor seluler Indonesia.
     */
    private const LOCAL_MIN = 8;

    private const LOCAL_MAX = 13;

    /**
     * Mengembalikan nomor dalam format `62...` atau `null` bila tidak
     * resembles nomor WhatsApp Indonesia.
     */
    public static function normalize(string $value): ?string
    {
        $digits = preg_replace('/\D+/', '', $value) ?? '';

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        }

        if (! str_starts_with($digits, '62')) {
            return null;
        }

        $local = substr($digits, 2);

        if (strlen($local) < self::LOCAL_MIN || strlen($local) > self::LOCAL_MAX) {
            return null;
        }

        return $digits;
    }
}
