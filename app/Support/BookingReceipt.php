<?php

namespace App\Support;

use Illuminate\Contracts\Session\Session;

/**
 * Ringkasan booking yang baru disimpan, disimpan sebentar di session.
 *
 * Session dipakai supaya halaman sukses tidak harus memuat booking lewat
 * URL. Memakai `booking_id` di URL berarti id booking bisa ditebak dan
 * dibaca orang lain, padahal halaman ini menampilkan NIK penyewa.
 *
 * Draft dihapus setelah booking tersimpan, jadi receipt ini yang menggantikannya
 * sebagai konteks layar konfirmasi. ROADMAP 3.12 memakai data yang sama untuk
 * menyusun pesan WhatsApp tanpa perlu membaca booking lagi.
 */
final class BookingReceipt
{
    public const SESSION_KEY = 'booking.receipt';

    /**
     * @param  array<string, mixed>  $data
     */
    public function write(array $data): void
    {
        $this->session()->put(self::SESSION_KEY, $data);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function read(): ?array
    {
        $receipt = $this->session()->get(self::SESSION_KEY);

        return is_array($receipt) ? $receipt : null;
    }

    public function forget(): void
    {
        $this->session()->forget(self::SESSION_KEY);
    }

    private function session(): Session
    {
        return app(Session::class);
    }
}
