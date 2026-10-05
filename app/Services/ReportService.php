<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethodType;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Business;
use App\Models\Payment;
use App\Support\BookingPeriod;
use Illuminate\Support\Carbon;

/**
 * Laporan periode untuk satu unit bisnis (PRD section 29, ROADMAP 5.1).
 *
 * Semua angka dihitung dari `business_id` yang diberikan pemanggil, bukan dari
 * admin yang login, supaya laporan tiap unit tidak pernah tercampur. Definisi
 * tiap angka ditulis di methodnya masing-masing; yang penting di sini adalah
 * pemisahan dua sumbu waktu yang sering tertukar di laporan:
 *
 * - Booking dihitung dari `created_at`, yaitu kapan transaksi masuk.
 *   "Booking selesai" dan "booking dibatalkan" memakai `completed_at` dan
 *   `cancelled_at`, karena yang ditanyakan laporan adalah kapan peristiwanya
 *   terjadi, bukan kapan bookingnya dibuat.
 * - Pendapatan dihitung dari pembayaran `lunas` dengan `verified_at` di dalam
 *   periode, sama seperti dashboard. Uang diakui saat admin mengonfirmasi,
 *   bukan saat booking dibuat.
 */
final class ReportService
{
    /**
     * Jumlah produk pada daftar produk paling banyak disewa.
     */
    public const TOP_PRODUCTS_LIMIT = 5;

    /**
     * Batas rentang yang masih digambar per hari. Lebih dari ini, grafik
     * berpindah ke per bulan supaya batangnya tidak menjadi garis rambut.
     */
    public const DAILY_CHART_MAX_DAYS = 31;

    /**
     * Seluruh isi laporan untuk rentang tanggal tertentu.
     *
     * @return array<string, mixed>
     */
    public function forPeriod(Business $business, string $from, string $to): array
    {
        $businessId = $business->getKey();
        $start = Carbon::parse($from)->startOfDay();
        $end = Carbon::parse($to)->endOfDay();

        $revenue = $this->revenue($businessId, $start, $end);

        return [
            'period' => [
                'from' => $start->toDateString(),
                'to' => $end->toDateString(),
                'label' => BookingPeriod::readableDate($start)
                    .' - '.BookingPeriod::readableDate($end),
                'days' => (int) $start->diffInDays($end) + 1,
            ],
            'stats' => [
                'bookings' => $this->bookings($businessId, $start, $end),
                'finished' => $this->finishedBookings($businessId, $start, $end),
                'cancelled' => $this->cancelledBookings($businessId, $start, $end),
                'customers' => $this->customers($businessId, $start, $end),
                'revenue' => $revenue,
                'revenue_label' => number_format($revenue, 0, ',', '.'),
            ],
            'top_products' => $this->topProducts($businessId, $start, $end),
            'payment_methods' => $this->paymentMethods($businessId, $start, $end),
            'revenue_chart' => $this->revenueChart($businessId, $start, $end),
        ];
    }

    /**
     * Jumlah booking yang masuk pada periode, berdasarkan kapan booking
     * dibuat.
     */
    private function bookings(int $businessId, Carbon $start, Carbon $end): int
    {
        return (int) Booking::query()
            ->where('business_id', $businessId)
            ->whereBetween('created_at', [$start, $end])
            ->count();
    }

    /**
     * Booking yang selesai pada periode, berdasarkan `completed_at`.
     *
     * Booking yang dibuat bulan lalu tapi baru selesai bulan ini dihitung di
     * bulan ini, karena laporan operasional menanyakan pekerjaan yang
     * diselesaikan pada periode itu.
     */
    private function finishedBookings(int $businessId, Carbon $start, Carbon $end): int
    {
        return (int) Booking::query()
            ->where('business_id', $businessId)
            ->whereBetween('completed_at', [$start, $end])
            ->count();
    }

    /**
     * Booking yang dibatalkan pada periode, berdasarkan `cancelled_at`.
     */
    private function cancelledBookings(int $businessId, Carbon $start, Carbon $end): int
    {
        return (int) Booking::query()
            ->where('business_id', $businessId)
            ->whereBetween('cancelled_at', [$start, $end])
            ->count();
    }

    /**
     * Jumlah penyewa berbeda yang membuat booking pada periode.
     *
     * Satu orang yang booking dua kali tetap dihitung satu, karena
     * pertanyaannya "berapa orang", bukan "berapa transaksi".
     */
    private function customers(int $businessId, Carbon $start, Carbon $end): int
    {
        return (int) Booking::query()
            ->where('business_id', $businessId)
            ->whereBetween('created_at', [$start, $end])
            ->distinct()
            ->count('customer_id');
    }

    /**
     * Pendapatan dari pembayaran lunas yang diverifikasi pada periode.
     */
    private function revenue(int $businessId, Carbon $start, Carbon $end): int
    {
        return (int) Payment::query()
            ->where('business_id', $businessId)
            ->where('status', PaymentStatus::Lunas->value)
            ->whereBetween('verified_at', [$start, $end])
            ->sum('amount');
    }

    /**
     * Produk paling banyak disewa pada periode.
     *
     * Nama produk dibaca dari `booking_items`, bukan dari `products`, supaya
     * booking lama tetap masuk rekap dengan nama yang tersimpan di booking itu
     * (BR-09). Booking yang dibatalkan tidak ikut: barangnya tidak pernah
     * benar-benar disewa.
     *
     * @return list<array<string, mixed>>
     */
    private function topProducts(int $businessId, Carbon $start, Carbon $end): array
    {
        $rows = BookingItem::query()
            ->join('bookings', 'bookings.id', '=', 'booking_items.booking_id')
            ->where('bookings.business_id', $businessId)
            ->whereBetween('bookings.created_at', [$start, $end])
            ->where('bookings.booking_status', '!=', BookingStatus::Dibatalkan->value)
            ->selectRaw(
                'booking_items.product_name as product_name,'
                .' SUM(booking_items.quantity) as total_quantity,'
                .' SUM(booking_items.subtotal) as total_subtotal'
            )
            ->groupBy('booking_items.product_name')
            ->orderByDesc('total_quantity')
            ->limit(self::TOP_PRODUCTS_LIMIT)
            ->get();

        $products = [];

        foreach ($rows as $row) {
            $revenue = (int) $row->getAttribute('total_subtotal');

            $products[] = [
                'product_name' => (string) $row->getAttribute('product_name'),
                'quantity' => (int) $row->getAttribute('total_quantity'),
                'revenue' => $revenue,
                'revenue_label' => number_format($revenue, 0, ',', '.'),
            ];
        }

        return $products;
    }

    /**
     * Rekap transaksi per metode pembayaran.
     *
     * Yang dihitung adalah pembayaran yang dibuat pada periode. `paid` hanya
     * menjumlahkan yang sudah lunas, sedangkan `amount` menjumlahkan seluruh
     * nominal transaksi, supaya selisih antara nilai transaksi dan uang yang
     * benar-benar masuk terlihat.
     *
     * Ketiga metode selalu ditampilkan walau tidak dipakai, supaya admin
     * melihat angka nol sebagai "tidak ada transaksi", bukan baris yang hilang.
     *
     * @return list<array<string, mixed>>
     */
    private function paymentMethods(int $businessId, Carbon $start, Carbon $end): array
    {
        $rows = Payment::query()
            ->where('business_id', $businessId)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw(
                'method, COUNT(*) as total_transactions,'
                .' SUM(amount) as total_amount,'
                .' SUM(CASE WHEN status = ? THEN amount ELSE 0 END) as total_paid',
                [PaymentStatus::Lunas->value]
            )
            ->groupBy('method')
            ->get()
            ->keyBy(fn (Payment $payment): string => $payment->method->value);

        $methods = [];

        foreach (PaymentMethodType::all() as $type) {
            $row = $rows->get($type->value);
            $amount = $row === null ? 0 : (int) $row->getAttribute('total_amount');
            $paid = $row === null ? 0 : (int) $row->getAttribute('total_paid');

            $methods[] = [
                'method' => $type->value,
                'method_label' => $type->label(),
                'transactions' => $row === null ? 0 : (int) $row->getAttribute('total_transactions'),
                'amount' => $amount,
                'amount_label' => number_format($amount, 0, ',', '.'),
                'paid' => $paid,
                'paid_label' => number_format($paid, 0, ',', '.'),
            ];
        }

        return $methods;
    }

    /**
     * Grafik pendapatan, per hari atau per bulan tergantung panjang periode.
     *
     * Titik data selalu lengkap, termasuk hari atau bulan tanpa pendapatan,
     * supaya grafik tidak terlihat melompati waktu. Definisi pendapatannya
     * sama dengan `revenue()`, jadi angka tertinggi di grafik selalu cocok
     * dengan kartu total pendapatan.
     *
     * @return array<string, mixed>
     */
    private function revenueChart(int $businessId, Carbon $start, Carbon $end): array
    {
        $days = (int) $start->diffInDays($end) + 1;
        $daily = $days <= self::DAILY_CHART_MAX_DAYS;

        $sums = $daily ? $this->dailySums($businessId, $start, $end) : $this->monthlySums($businessId, $start, $end);

        $points = [];
        $total = 0;

        if ($daily) {
            $cursor = $start->copy();

            while ($cursor->lessThanOrEqualTo($end)) {
                $key = $cursor->toDateString();
                $amount = (int) ($sums[$key] ?? 0);
                $total += $amount;

                $points[] = [
                    'key' => $key,
                    'label' => BookingPeriod::readableShortDate($cursor),
                    'full_label' => BookingPeriod::readableDate($cursor),
                    'amount' => $amount,
                    'amount_label' => number_format($amount, 0, ',', '.'),
                ];

                $cursor->addDay();
            }
        } else {
            // Tanggal awal tiap bulan dibuat lewat `createFromFormat` supaya
            // tipenya tetap `Carbon` yang jelas, bukan hasil fluent method yang
            // bisa dibaca union oleh analisis statis.
            $cursor = Carbon::createFromFormat('Y-m-d', $start->format('Y-m-01'));
            $last = Carbon::createFromFormat('Y-m-d', $end->format('Y-m-01'));

            while ($cursor->lessThanOrEqualTo($last)) {
                $key = $cursor->format('Y-m');
                $amount = (int) ($sums[$key] ?? 0);
                $total += $amount;

                $points[] = [
                    'key' => $key,
                    'label' => BookingPeriod::readableShortMonth($cursor),
                    'full_label' => BookingPeriod::readableMonth($cursor),
                    'amount' => $amount,
                    'amount_label' => number_format($amount, 0, ',', '.'),
                ];

                $cursor->addMonth();
            }
        }

        return [
            'granularity' => $daily ? 'harian' : 'bulanan',
            'points' => $points,
            'total' => $total,
            'total_label' => number_format($total, 0, ',', '.'),
        ];
    }

    /**
     * Jumlah pendapatan per hari pada periode.
     *
     * @return array<string, mixed>
     */
    private function dailySums(int $businessId, Carbon $start, Carbon $end): array
    {
        return Payment::query()
            ->where('business_id', $businessId)
            ->where('status', PaymentStatus::Lunas->value)
            ->whereBetween('verified_at', [$start, $end])
            ->selectRaw('DATE(verified_at) as bucket, SUM(amount) as aggregate')
            ->groupBy('bucket')
            ->pluck('aggregate', 'bucket')
            ->all();
    }

    /**
     * Jumlah pendapatan per bulan pada periode.
     *
     * @return array<string, mixed>
     */
    private function monthlySums(int $businessId, Carbon $start, Carbon $end): array
    {
        return Payment::query()
            ->where('business_id', $businessId)
            ->where('status', PaymentStatus::Lunas->value)
            ->whereBetween('verified_at', [$start, $end])
            ->selectRaw("DATE_FORMAT(verified_at, '%Y-%m') as bucket, SUM(amount) as aggregate")
            ->groupBy('bucket')
            ->pluck('aggregate', 'bucket')
            ->all();
    }
}
