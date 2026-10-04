<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Support\BookingPeriod;
use Illuminate\Database\Eloquent\Builder;

/**
 * Angka dashboard admin untuk satu unit bisnis (PRD section 22, ROADMAP 4.1).
 *
 * Semua angka dihitung dari satu `business_id` yang diberikan pemanggil,
 * bukan dari admin yang sedang login. `BusinessScope` tetap ikut menyaring
 * query sebagai lapisan kedua (BR-05), jadi kalau ada pemanggil yang salah
 * parameter, hasilnya booking kosong, bukan angka unit lain.
 *
 * Definisi angka yang dipakai di sini:
 *
 * - Pendapatan hanya menghitung pembayaran yang sudah `lunas` dan dikonfirmasi
 *   admin pada bulan berjalan. Booking yang belum dibayar tidak dihitung
 *   supaya angka dashboard tidak lebih besar dari uang yang sudah benar-benar
 *   masuk, dan jumlahnya ditampilkan terpisah di `revenue.pending`.
 * - Booking hari ini memakai tanggal booking dibuat, mengikuti timezone
 *   aplikasi, bukan tanggal mulai sewa.
 */
class DashboardService
{
    /**
     * Jumlah booking di widget booking terbaru.
     */
    public const RECENT_BOOKINGS_LIMIT = 5;

    /**
     * Jumlah hari pada grafik booking.
     */
    public const CHART_DAYS = 7;

    /**
     * Seluruh angka dashboard untuk satu unit bisnis.
     *
     * @return array<string, mixed>
     */
    public function stats(Business $business): array
    {
        return [
            'totalProducts' => (int) Product::query()
                ->where('business_id', $business->getKey())
                ->where('is_active', true)
                ->count(),
            'bookingsToday' => (int) $this->bookings($business)
                ->whereDate('created_at', today())
                ->count(),
            'rented' => (int) $this->bookings($business)
                ->where('booking_status', 'sedang_disewa')
                ->count(),
            'awaitingConfirmation' => (int) $this->bookings($business)
                ->where('booking_status', 'menunggu_konfirmasi')
                ->count(),
            'pendingPayments' => (int) Payment::query()
                ->where('business_id', $business->getKey())
                ->where('status', PaymentStatus::MenungguVerifikasi->value)
                ->count(),
            'revenue' => $this->revenue($business),
            'bookingChart' => $this->bookingChart($business),
            'recentBookings' => $this->recentBookings($business),
        ];
    }

    /**
     * Pendapatan bulan berjalan, dipisahkan dari yang belum diverifikasi.
     *
     * `verified_at` dipakai sebagai penanda waktu pendapatan diakui, karena itu
     * saat admin benar-benar mengonfirmasi pembayaran. Pembayaran yang belum
     * lunas dihitung dari `created_at`, yaitu saat payment record dibuat, supaya
     * jumlahnya tidak terlihat Rp0 padahal masih ada yang menunggu verifikasi.
     *
     * @return array<string, mixed>
     */
    private function revenue(Business $business): array
    {
        $today = today();
        $monthStart = $today->copy()->startOfMonth();

        $paid = (int) Payment::query()
            ->where('business_id', $business->getKey())
            ->where('status', PaymentStatus::Lunas->value)
            ->where('verified_at', '>=', $monthStart)
            ->sum('amount');

        $pending = (int) Payment::query()
            ->where('business_id', $business->getKey())
            ->whereIn('status', [
                PaymentStatus::BelumDibayar->value,
                PaymentStatus::MenungguVerifikasi->value,
            ])
            ->where('created_at', '>=', $monthStart)
            ->sum('amount');

        return [
            'paid' => $paid,
            'paid_label' => number_format($paid, 0, ',', '.'),
            'pending' => $pending,
            'pending_label' => number_format($pending, 0, ',', '.'),
            'period_label' => BookingPeriod::readableDate($monthStart).' - '.BookingPeriod::readableDate($today->copy()->endOfMonth()),
        ];
    }

    /**
     * Jumlah booking per hari untuk tujuh hari terakhir, termasuk hari ini.
     *
     * Ketujuh titik selalu dikirim, termasuk hari tanpa booking, supaya grafik
     * tidak terlihat melompati tanggal dan label sumbu-x bisa ditulis tanggal
     * aslinya.
     *
     * @return list<array<string, mixed>>
     */
    private function bookingChart(Business $business): array
    {
        $today = today();
        $from = $today->copy()->subDays(self::CHART_DAYS - 1)->startOfDay();
        $until = $today->copy()->addDay()->startOfDay();

        $counts = $this->bookings($business)
            ->where('created_at', '>=', $from)
            ->where('created_at', '<', $until)
            ->selectRaw('DATE(created_at) as bucket, COUNT(*) as aggregate')
            ->groupBy('bucket')
            ->pluck('aggregate', 'bucket');

        $points = [];

        for ($day = 0; $day < self::CHART_DAYS; $day++) {
            $date = $from->copy()->addDays($day);

            $points[] = [
                'date' => $date->toDateString(),
                'label' => BookingPeriod::readableShortDate($date),
                'full_label' => BookingPeriod::readableDate($date),
                'count' => (int) ($counts[$date->toDateString()] ?? 0),
            ];
        }

        return $points;
    }

    /**
     * Booking terbaru milik unit tersebut.
     *
     * Customer dan item di-eager-load supaya daftar ini tetap beberapa query,
     * bukan satu query per baris.
     *
     * @return list<array<string, mixed>>
     */
    private function recentBookings(Business $business): array
    {
        $bookings = $this->bookings($business)
            ->with(['customer', 'items'])
            ->latest('id')
            ->limit(self::RECENT_BOOKINGS_LIMIT)
            ->get();

        $rows = [];

        foreach ($bookings as $booking) {
            $rows[] = [
                'booking_code' => (string) $booking->booking_code,
                'customer_name' => $this->customerName($booking),
                'product_name' => $this->productName($booking),
                'quantity' => (int) $booking->items->sum('quantity'),
                'period_label' => BookingPeriod::readableShortDate($booking->start_date).' - '.BookingPeriod::readableShortDate($booking->end_date),
                'total' => (int) $booking->total,
                'total_label' => number_format((int) $booking->total, 0, ',', '.'),
                'status' => $booking->booking_status->value,
                'status_label' => $booking->booking_status->label(),
            ];
        }

        return $rows;
    }

    /**
     * Query booking dasar yang selalu ter-scope ke satu unit bisnis.
     *
     * @return Builder<Booking>
     */
    private function bookings(Business $business): Builder
    {
        return Booking::query()->where('bookings.business_id', $business->getKey());
    }

    private function customerName(Booking $booking): string
    {
        $customer = $booking->customer;

        if (! $customer instanceof Customer) {
            return '-';
        }

        return $customer->name;
    }

    /**
     * Nama produk diambil dari `booking_items`, bukan dari `products`, supaya
     * daftar booking tetap terbaca apa adanya walaupun produknya diubah atau
     * produknya sudah tidak terhubung ke unit ini.
     */
    private function productName(Booking $booking): string
    {
        $item = $booking->items->first();

        if (! $item instanceof BookingItem) {
            return '-';
        }

        return $item->product_name;
    }
}
