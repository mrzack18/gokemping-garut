<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Laporan periode admin (PRD section 29, ROADMAP 5.1).
 *
 * Controller ini hanya menormalkan filter tanggal; seluruh angkanya dihitung
 * `ReportService` untuk unit bisnis admin yang login, jadi laporan tiap unit
 * tidak pernah tercampur (BR-05).
 */
class ReportController extends Controller
{
    /**
     * Laporan untuk rentang tanggal yang dipilih.
     */
    public function index(Request $request, ReportService $reports): Response
    {
        $business = $request->user()->business;
        $filters = $this->filters($request);

        return Inertia::render('admin/reports/index', [
            'filters' => $filters,
            'report' => $reports->forPeriod($business, $filters['from'], $filters['to']),
        ]);
    }

    /**
     * Rentang tanggal dari query string, sudah ternormalisasi.
     *
     * Default-nya bulan berjalan sampai hari ini, karena itu periode yang
     * paling sering dibuka admin. Tanggal yang tidak valid diabaikan, bukan
     * dibalas 422: laporan adalah halaman kerja, bukan form.
     *
     * Kalau tanggal mulai dan selesai tertukar, keduanya ditukar balik. Admin
     * yang salah urut jelas bermaksud melihat periode di antara kedua tanggal
     * itu, dan laporan kosong hanya akan membuatnya mengira tidak ada data.
     *
     * @return array{from: string, to: string}
     */
    private function filters(Request $request): array
    {
        $today = today();

        $from = $this->dateFilter($request->query('from'))
            ?? $today->copy()->startOfMonth()->toDateString();
        $to = $this->dateFilter($request->query('to'))
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
     * Pola yang sama dipakai filter daftar booking: nilai yang tidak benar-benar
     * ada seperti `2026-02-31` ditolak di sini, bukan diteruskan ke query dan
     * diam-diam tidak cocok dengan apa pun.
     */
    private function dateFilter(mixed $value): ?string
    {
        if (! is_string($value) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return null;
        }

        $date = Carbon::createFromFormat('Y-m-d', $value);

        return $date->format('Y-m-d') === $value ? $value : null;
    }
}
