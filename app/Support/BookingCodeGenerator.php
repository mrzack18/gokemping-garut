<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\Business;
use App\Models\Scopes\BusinessScope;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * Pembuat kode booking unik (BR-10, PRD section 20).
 *
 * Format kode mengikuti `booking_code_prefix` milik unit bisnis, lalu tanggal
 * dibuat, lalu nomor urut tiga digit:
 *
 *     GK-20261003-001
 *     SSG-20261003-001
 *
 * Nomor urut dihitung ulang dari kode yang sudah ada pada tanggal tersebut,
 * bukan dari counter terpisah, supaya tidak ada state yang bisa rusak kalau
 * ada kode terhapus atau di-seed di luar aplikasi.
 */
final class BookingCodeGenerator
{
    /**
     * Jumlah digit nomor urut.
     */
    private const SEQUENCE_PADDING = 3;

    /**
     * Batas percobaan mencari slot yang bebas.
     *
     * Kode yang sama hanya mungkin terjadi kalau ada dua request bersamaan
     * membaca urutan terakhir yang sama sebelum salah satunya commit. Indeks
     * unik `bookings.booking_code` yang akhirnya menolak duplikat, dan retry
     * di sini membuat konflik itu jarang muncul ke pengguna.
     */
    private const MAX_ATTEMPTS = 5;

    /**
     * Kode booking berikutnya untuk satu unit bisnis pada tanggal dibuat.
     *
     * Wajib dipanggil dari dalam transaksi yang sudah mengunci baris `business`
     * dengan `lockForUpdate`, tepat seperti `BookingService` lakukan. Baris
     * business adalah mutex yang selalu ada, sedangkan baris booking belum ada
     * pada kode pertama sehingga tidak bisa di anchoring lock. Tanpa lock itu
     * dua request bersamaan bisa sama-sama membaca urutan terakhir yang sama,
     * dan kode yang sama akan ditolak indeks unik.
     */
    public function next(Business $business, Carbon $date): string
    {
        $prefix = $business->booking_code_prefix;
        $day = $date->format('Ymd');
        $stem = $prefix.'-'.$day.'-';

        $sequence = $this->lastSequence($business, $stem);

        for ($attempt = 0; $attempt < self::MAX_ATTEMPTS; $attempt++) {
            $candidate = $stem.str_pad((string) ($sequence + 1), self::SEQUENCE_PADDING, '0', STR_PAD_LEFT);

            if (! $this->exists($business, $candidate)) {
                return $candidate;
            }

            $sequence++;
        }

        /**
         * Lima slot berurutan semuanya terisi berarti ada proses lain yang
         * sangat aktif menulis booking untuk unit ini. Lebih baik gagal dengan
         * pesan jelas daripada diam-diam memakai kode yang sudah terpakai.
         */
        throw new RuntimeException("Kode booking untuk {$prefix} pada {$day} tidak bisa dibuat.");
    }

    /**
     * Nomor urut terakhir yang terpakai untuk prefix dan tanggal tersebut.
     *
     * Kode dibaca menurun secara leksikal. Selama nomor urut masih muat tiga
     * digit, urutan leksikal sama dengan urutan numerik. Setelah melewati 999
     * kode per hari, kode empat digit mulai bersaing dengan kode tiga digit,
     * dan sisipan `exists()` yang menutup celah itu.
     */
    private function lastSequence(Business $business, string $stem): int
    {
        $lastCode = BusinessScope::withoutBusinessScope(
            Booking::query()
                ->where('business_id', $business->getKey())
                ->where('booking_code', 'like', $stem.'%')
                ->orderByDesc('booking_code'),
        )->value('booking_code');

        if (! is_string($lastCode)) {
            return 0;
        }

        return (int) substr($lastCode, strlen($stem));
    }

    private function exists(Business $business, string $code): bool
    {
        return BusinessScope::withoutBusinessScope(
            Booking::query()
                ->where('business_id', $business->getKey())
                ->where('booking_code', $code),
        )->exists();
    }
}
