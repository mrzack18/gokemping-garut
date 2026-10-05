<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Normalisasi filter tanggal laporan (ROADMAP 5.1 & 5.2).
 *
 * Dipakai bersama oleh halaman laporan dan ekspornya, supaya berkas yang
 * diunduh memuat periode yang sama dengan yang sedang dilihat admin.
 *
 * Default-nya bulan berjalan sampai hari ini, karena itu periode yang paling
 * sering dibuka admin. Tanggal yang tidak valid diabaikan, bukan dibalas 422:
 * laporan adalah halaman kerja, bukan form. Kalau tanggal mulai dan selesai
 * tertukar, keduanya ditukar balik; admin yang salah urut jelas bermaksud
 * melihat periode di antara kedua tanggal itu.
 */
final class ReportFilters
{
    /**
     * @return array{from: string, to: string}
     */
    public static function fromRequest(Request $request): array
    {
        $today = today();

        $from = self::date($request->query('from'))
            ?? $today->copy()->startOfMonth()->toDateString();
        $to = self::date($request->query('to'))
            ?? $today->toDateString();

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        return ['from' => $from, 'to' => $to];
    }

    /**
     * Tanggal dari query string dalam bentuk `YYYY-MM-DD`, atau null kalau tidak
     * valid.
     *
     * Tanggal tidak dipakai langsung dari query string, karena `whereDate` akan
     * memperlakukannya sebagai teks dan nilainya bisa disisipkan apa adanya.
     * `Carbon::createFromFormat()` dipakai supaya tanggal yang tidak benar-benar
     * ada, seperti `2026-02-31`, ditolak di sini dan tidak sampai jadi filter
     * yang diam-diam tidak cocok dengan apa pun.
     */
    private static function date(mixed $value): ?string
    {
        if (! is_string($value) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return null;
        }

        $date = Carbon::createFromFormat('Y-m-d', $value);

        return $date->format('Y-m-d') === $value ? $value : null;
    }
}
