<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Business;
use App\Models\Payment;
use App\Support\BookingPeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Statistik tahunan untuk satu unit bisnis (ROADMAP 5.3).
 *
 * Berbeda dari laporan (5.1) yang mengikuti periode bebas, halaman statistik
 * selalu memakai satu tahun penuh supaya pertanyaan yang dijawabnya konsisten:
 * bagaimana tren bulanan tahun ini, produk apa yang paling laris tahun ini,
 * dan metode apa yang paling banyak dipakai tahun ini.
 *
 * Definisi angkanya sengaja memakai sumber yang sama dengan laporan dan
 * dashboard: booking dari `created_at`, pendapatan dari pembayaran `lunas`
 * yang `verified_at`-nya jatuh di dalam tahun tersebut. Rekap produk dan
 * metode memakai `ReportService` untuk rentang setahun, jadi angka di halaman
 * ini tidak bisa berbeda dari laporan periode yang sama.
 */
final class StatisticsService
{
    /**
     * Jumlah bulan dalam satu tahun.
     */
    public const MONTHS_IN_YEAR = 12;

    /**
     * Jumlah tahun yang ditampilkan di pemilih tahun.
     */
    public const YEAR_OPTIONS_LIMIT = 10;

    public function __construct(private readonly ReportService $reports) {}

    /**
     * Seluruh statistik satu tahun.
     *
     * @return array<string, mixed>
     */
    public function forYear(Business $business, int $year): array
    {
        $businessId = $business->getKey();
        $report = $this->reports->forPeriod(
            $business,
            $year.'-01-01',
            $year.'-12-31',
        );

        return [
            'year' => $year,
            'years' => $this->availableYears($businessId),
            'booking_chart' => [
                'points' => $this->bookingChart($businessId, $year),
            ],
            'revenue_chart' => $this->revenueChart($report['revenue_chart']),
            'top_products' => $report['top_products'],
            'payment_methods' => $this->paymentMethods($report['payment_methods']),
        ];
    }

    /**
     * Jumlah booking yang masuk per bulan pada tahun tersebut.
     *
     * Dua belas titik selalu dikirim, termasuk bulan tanpa booking, supaya
     * batang kosong terlihat sebagai "belum ada booking" dan bukan bulan yang
     * hilang dari grafik.
     *
     * @return list<array<string, mixed>>
     */
    private function bookingChart(int $businessId, int $year): array
    {
        $counts = Booking::query()
            ->where('business_id', $businessId)
            ->whereYear('created_at', $year)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as bucket, COUNT(*) as aggregate")
            ->groupBy('bucket')
            ->pluck('aggregate', 'bucket');

        $points = [];

        for ($month = 1; $month <= self::MONTHS_IN_YEAR; $month++) {
            $date = $this->monthStart($year, $month);
            $key = $date->format('Y-m');
            $count = (int) ($counts[$key] ?? 0);

            $points[] = [
                'key' => $key,
                'label' => BookingPeriod::readableShortMonth($date),
                'full_label' => BookingPeriod::readableMonth($date),
                'value' => $count,
                'value_label' => (string) $count,
            ];
        }

        return $points;
    }

    /**
     * Grafik pendapatan bulanan, disalin dari rekap laporan dengan nama field
     * yang seragam dengan grafik booking supaya frontend bisa memakai satu
     * komponen grafik untuk keduanya.
     *
     * @param  array<string, mixed>  $chart
     * @return array<string, mixed>
     */
    private function revenueChart(array $chart): array
    {
        $points = [];

        foreach ($chart['points'] as $point) {
            $points[] = [
                'key' => $point['key'],
                'label' => $point['label'],
                'full_label' => $point['full_label'],
                'value' => $point['amount'],
                'value_label' => $point['amount_label'],
            ];
        }

        return [
            'points' => $points,
            'total' => $chart['total'],
            'total_label' => $chart['total_label'],
        ];
    }

    /**
     * Rekap metode pembayaran, diurutkan dari yang paling banyak dipakai.
     *
     * Urutan ini yang membedakannya dari rekap di laporan: di laporan semua
     * metode tampil dengan urutan tetap, di statistik yang paling sering
     * dipakai naik ke atas supaya langsung terbaca.
     *
     * @param  list<array<string, mixed>>  $methods
     * @return list<array<string, mixed>>
     */
    private function paymentMethods(array $methods): array
    {
        return array_values(
            Collection::make($methods)
                ->sortByDesc('transactions')
                ->values()
                ->all()
        );
    }

    /**
     * Tahun yang punya data, ditambah tahun berjalan.
     *
     * Tahun berjalan selalu ikut walau belum ada transaksinya, karena halaman
     * statistik harus tetap bisa dibuka di unit yang baru mulai.
     *
     * @return list<int>
     */
    private function availableYears(int $businessId): array
    {
        $bookingYears = Booking::query()
            ->where('business_id', $businessId)
            ->selectRaw('DISTINCT YEAR(created_at) as year')
            ->pluck('year');

        $paymentYears = Payment::query()
            ->where('business_id', $businessId)
            ->whereNotNull('verified_at')
            ->selectRaw('DISTINCT YEAR(verified_at) as year')
            ->pluck('year');

        return array_values(
            $bookingYears
                ->merge($paymentYears)
                ->map(fn (mixed $year): int => (int) $year)
                ->push((int) today()->format('Y'))
                ->unique()
                ->sortDesc()
                ->take(self::YEAR_OPTIONS_LIMIT)
                ->all()
        );
    }

    /**
     * Awal bulan sebagai `Carbon` yang tipenya jelas untuk analisis statis.
     */
    private function monthStart(int $year, int $month): Carbon
    {
        return Carbon::createFromFormat(
            'Y-m-d',
            sprintf('%04d-%02d-01', $year, $month),
        );
    }
}
