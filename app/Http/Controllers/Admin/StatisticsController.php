<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\StatisticsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Statistik tahunan admin (ROADMAP 5.3).
 *
 * Controller ini hanya menormalkan pilihan tahun; seluruh angkanya dihitung
 * `StatisticsService` untuk unit bisnis admin yang login (BR-05).
 */
class StatisticsController extends Controller
{
    /**
     * Statistik satu tahun, default tahun berjalan.
     */
    public function index(Request $request, StatisticsService $statistics): Response
    {
        $business = $request->user()->business;
        $year = $this->year($request);

        return Inertia::render(
            'admin/statistics/index',
            $statistics->forYear($business, $year),
        );
    }

    /**
     * Tahun dari query string, atau tahun berjalan kalau tidak valid.
     *
     * Nilai yang aneh dibiarkan jatuh ke default, bukan dibalas 422: halaman
     * statistik adalah halaman kerja, bukan form. Batas bawah dan atas menjaga
     * nilai yang masuk tetap tahun yang masuk akal.
     */
    private function year(Request $request): int
    {
        $value = $request->query('year');

        if (is_string($value) && preg_match('/^\d{4}$/', $value) === 1) {
            $year = (int) $value;

            if ($year >= 2000 && $year <= 2100) {
                return $year;
            }
        }

        return (int) today()->format('Y');
    }
}
