<?php

namespace App\Support;

/**
 * Token tiket untuk QR dan tautan pindai (ROADMAP 5.5 lanjutan).
 *
 * QR tiket berisi URL halaman cek tiket dengan token bertanda tangan, jadi
 * memindai QR sama dengan membuka tiket tanpa mengetik kode booking dan nomor
 * WhatsApp. Tokennya HMAC dari kode booking memakai `APP_KEY`, sehingga URL
 * hanya bisa dibuat aplikasi ini dan kode booking yang diubah-ubah akan
 * ditolak.
 *
 * Siapa pun yang memegang QR memang bisa membuka tiketnya — sama seperti tiket
 * fisik — tetapi payload tiket tidak memuat NIK maupun alamat, dan halaman
 * publik tidak pernah menampilkan data itu.
 */
final class TicketToken
{
    /**
     * Token untuk satu kode booking.
     */
    public static function make(string $bookingCode): string
    {
        return hash_hmac('sha256', $bookingCode, (string) config('app.key'));
    }

    /**
     * Cocokkan token dengan kode booking-nya.
     */
    public static function matches(string $bookingCode, string $token): bool
    {
        return hash_equals(self::make($bookingCode), $token);
    }

    /**
     * URL pindai tiket yang dipasang di QR.
     */
    public static function url(string $bookingCode): string
    {
        return route('tickets.scan', [
            'booking' => $bookingCode,
            'token' => self::make($bookingCode),
        ]);
    }
}
