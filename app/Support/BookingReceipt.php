<?php

namespace App\Support;

use Illuminate\Contracts\Session\Session;

/**
 * Ringkasan booking yang baru disimpan, disimpan sebentar di session.
 *
 * Session dipakai supaya halaman sukses tidak harus memuat booking lewat
 * URL. Memakai `booking_id` di URL berarti id booking bisa ditebak dan
 * dibaca orang lain, padahal halaman ini menampilkan data penyewa.
 *
 * Receipt juga punya masa berlaku (`TTL_HOURS`). Tanpa batas waktu, siapa pun
 * yang memakai peramban yang sama nanti — mis. komputer warnet atau perangkat
 * bersama — masih bisa membuka halaman sukses lewat riwayat dan membaca data
 * booking orang sebelumnya. Setelah kedaluwarsa, penyewa tetap bisa membuka
 * tiketnya lewat cek tiket (kode booking + WhatsApp) atau QR yang diunduh.
 *
 * Draft dihapus setelah booking tersimpan, jadi receipt ini yang menggantikannya
 * sebagai konteks layar konfirmasi. ROADMAP 3.12 memakai data yang sama untuk
 * menyusun pesan WhatsApp tanpa perlu membaca booking lagi.
 */
final class BookingReceipt
{
    public const SESSION_KEY = 'booking.receipt';

    /**
     * Masa berlaku receipt, dalam jam.
     *
     * Dua belas jam cukup untuk menyelesaikan pesan WhatsApp, mengunduh QR,
     * dan membuka ulang halaman di perangkat yang sama, tanpa menyimpan data
     * penyewa di session lebih lama dari yang dibutuhkan.
     */
    public const TTL_HOURS = 12;

    /**
     * @param  array<string, mixed>  $data
     */
    public function write(array $data): void
    {
        $this->session()->put(self::SESSION_KEY, [
            'expires_at' => now()->addHours(self::TTL_HOURS)->getTimestamp(),
            'data' => $data,
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function read(): ?array
    {
        $envelope = $this->session()->get(self::SESSION_KEY);

        if (! is_array($envelope)) {
            return null;
        }

        $expiresAt = $envelope['expires_at'] ?? null;
        $data = $envelope['data'] ?? null;

        if (
            ! is_int($expiresAt)
            || ! is_array($data)
            || now()->getTimestamp() > $expiresAt
        ) {
            $this->forget();

            return null;
        }

        return $data;
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
