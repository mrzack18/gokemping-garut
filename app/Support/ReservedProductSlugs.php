<?php

namespace App\Support;

/**
 * Slug produk yang tidak boleh dipakai (ROADMAP 4.3).
 *
 * Alasan daftar ini ada bukan karena fiturnya belum selesai, tapi karena
 * URL katalog dan booking memakai path yang sama. Path publik per unit berbentuk
 * `/{prefix}/{product}` dan `/{prefix}/booking/{product}`, sementara beberapa
 * path di bawah `booking/` didaftarkan sebagai route literal lebih dulu supaya
 * tidak tertangkap sebagai slug produk.
 *
 * Produk dengan slug yang sama dengan salah satu path literal tidak membuat
 * URL-nya rusak: route literal menang dan pengunjung diarahkan ke halaman yang
 * lain. Contohnya slug `biodata` membuat `/gokemping/booking/biodata` membuka
 * halaman biodata penyewa, bukan halaman pemesanan produk tersebut. Untuk
 * produk, ini bukan sekadar tautan yang salah — satu langkah alur booking bisa
 * tidak bisa dicapai untuk produk itu saja.
 */
final class ReservedProductSlugs
{
    /**
     * @var list<string>
     */
    public const SLUGS = [
        // Segmen pertama di bawah path katalog. `booking` dipakai sebagai
        // prefix oleh `/gokemping/booking/{product}` dan route literalnya, jadi
        // produk dengan slug ini bercampur dengan seluruh alur booking.
        'booking',

        // Route literal di bawah `booking/`: `/booking/biodata`,
        // `/booking/review`, `/booking/payment`, dan `/booking/success`. Route
        // literal didaftarkan lebih dulu supaya tidak tertangkap sebagai slug
        // produk.
        'biodata',
        'review',
        'payment',
        'success',
    ];

    public static function isReserved(string $slug): bool
    {
        return in_array($slug, self::SLUGS, true);
    }
}
