<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Perhitungan periode dan total booking yang dipakai bersama oleh halaman
 * review (ROADMAP 3.8) dan penyimpanan booking (ROADMAP 3.11).
 *
 *_aturan durasi mengikuti keputusan ROADMAP 3.5: durasi adalah selisih tanggal
 * selesai dikurangi tanggal mulai, tanggal selesai diperlakukan sebagai batas
 * pengembalian sehingga tidak ikut dihitung, dan periode satu hari tetap
 * bernilai 1 hari supaya tidak ada booking bernilai nol rupiah.
 *
 * Nilai yang dihitung di sini selalu dikirim ke frontend sebagai angka siap
 * tampil, sehingga total yang dibaca penyewa sama dengan total yang nanti
 * disimpan, bukan hasil hitungan ulang di browser.
 */
final class BookingPeriod
{
    /**
     * Durasi sewa dalam hari. Minimal 1 hari.
     *
     * Pemanggil bertanggung jawab memastikan tanggal selesai tidak lebih awal
     * dari tanggal mulai; validasi itu sudah terjadi di FormRequest saat draft
     * disimpan.
     */
    public static function durationInDays(string $startDate, string $endDate): int
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();

        // `diffInDays` bernilai negatif kalau argumen lebih awal dari tanggal
        // instance ini, jadi selisih diambil secara absolut.
        $diff = (int) $end->diffInDays($start, true);

        return $diff > 0 ? $diff : 1;
    }

    /**
     * Total sewa satu item: harga satuan per hari dikali jumlah unit dan
     * durasi. Harga selalu disalin dari `products` saat dipanggil, tidak pernah
     * dari nilai yang dikirim klien (BR-09).
     */
    public static function total(int $pricePerUnit, int $quantity, int $duration): int
    {
        return $pricePerUnit * max(0, $quantity) * max(0, $duration);
    }

    /**
     * Tanggal periode dalam bentuk siap tampil, contoh `10 Oktober 2026`.
     */
    public static function readableDate(string|CarbonInterface $date): string
    {
        $date = $date instanceof CarbonInterface
            ? $date
            : Carbon::parse($date);

        return $date->locale('id')->translatedFormat('j F Y');
    }

    /**
     * Tanggal singkat untuk label rapat, contoh `29 Okt`.
     *
     * Dipakai di grafik dashboard, di mana tanggal penuh akan memenuhi sumbu.
     */
    public static function readableShortDate(string|CarbonInterface $date): string
    {
        $date = $date instanceof CarbonInterface
            ? $date
            : Carbon::parse($date);

        return $date->locale('id')->translatedFormat('j M');
    }
}
