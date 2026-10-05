<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Business;
use App\Models\Customer;
use App\Support\BookingPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Manajemen penyewa (PRD section 25, ROADMAP 4.5).
 *
 * Berbeda dari modul admin lain, `Customer` tidak punya `business_id`: satu
 * orang bisa menyewa di kedua unit bisnis. Karena itu daftar di sini tidak
 * menyaring baris customer, melainkan menyaring customer yang punya booking di
 * unit bisnis admin yang sedang login. Admin unit lain tidak bisa melihat
 * customer ini, walau barisnya ada di tabel yang sama.
 *
 * Isolasi tenant karena itu dilakukan lewat booking, bukan lewat baris customer.
 * Setiap query memakai `whereHas('bookings')` atau `$customer->bookings()`
 * dengan `business_id` eksplisit, dan detail customer yang tidak punya booking
 * di unit ini berakhir sebagai 404, bukan 403.
 *
 * NIK disimpan apa adanya supaya bisa dicari (PRD section 33), tapi yang
 * dikirim ke browser selalu `Customer::maskedNik()`.
 */
class CustomerController extends Controller
{
    /**
     * Jumlah penyewa per halaman.
     */
    public const PER_PAGE = 20;

    /**
     * Panjang maksimum kata kunci pencarian.
     */
    private const MAX_SEARCH_LENGTH = 100;

    /**
     * Daftar penyewa dengan pencarian nama, WhatsApp, dan NIK.
     */
    public function index(Request $request): Response
    {
        $business = $request->user()->business;
        $filters = $this->filters($request);

        return Inertia::render('admin/customers/index', [
            'customers' => $this->customers($business, $filters),
            'filters' => $filters,
        ]);
    }

    /**
     * Detail penyewa: data dirinya, ringkasan transaksi, dan riwayat booking
     * yang tercatat di unit bisnis ini saja.
     */
    public function show(Request $request, Customer $customer): Response
    {
        $business = $request->user()->business;

        if (! $this->belongsToBusiness($customer, $business)) {
            abort(404);
        }

        return Inertia::render('admin/customers/show', [
            'customer' => $this->detail($customer, $business),
            'bookings' => $this->bookings($customer, $business),
        ]);
    }

    /**
     * Daftar penyewa beserta ringkasan transaksinya, dengan filter yang sudah
     * diterapkan.
     *
     * Query dasarnya hanya customer yang punya minimal satu booking di unit ini.
     * Jumlah booking dan total transaksi dihitung dengan agregasi pada query
     * yang sama, bukan satu query per baris, dan keduanya dibatasi ke unit ini
     * supaya customer yang juga menyewa di unit lain tidak menampilkan angka
     * gabungan.
     *
     * @param  array{q: string}  $filters
     * @return array<string, mixed>
     */
    private function customers(Business $business, array $filters): array
    {
        $businessId = $business->getKey();

        $query = Customer::query()
            ->whereHas('bookings', fn (Builder $query): Builder => $query->where('bookings.business_id', $businessId))
            ->withCount([
                'bookings as bookings_count' => fn (Builder $query): Builder => $query->where('bookings.business_id', $businessId),
            ])
            ->withSum([
                'bookings as total_transaction' => fn (Builder $query): Builder => $query
                    ->where('bookings.business_id', $businessId)
                    ->where('bookings.booking_status', '!=', BookingStatus::Dibatalkan->value),
            ], 'total');

        if ($filters['q'] !== '') {
            $this->search($query, $filters['q']);
        }

        $customers = $query
            ->orderBy('customers.name')
            ->orderBy('customers.id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return [
            'data' => $customers->getCollection()
                ->map(fn (Customer $customer): array => $this->row($customer))
                ->values()
                ->all(),
            'current_page' => $customers->currentPage(),
            'last_page' => $customers->lastPage(),
            'from' => $customers->firstItem(),
            'to' => $customers->lastItem(),
            'total' => $customers->total(),
            'per_page' => $customers->perPage(),
        ];
    }

    /**
     * Pencarian bebas pada nama, WhatsApp, dan NIK.
     *
     * NIK dicari dalam bentuk aslinya, karena itulah alasan nilainya tetap
     * disimpan lengkap (PRD section 33). Yang ditampilkan ke admin tetap bentuk
     * tersamar.
     *
     * @param  Builder<Customer>  $query
     */
    private function search(Builder $query, string $term): void
    {
        $term = '%'.addcslashes($term, '\\%_').'%';

        $query->where(function (Builder $query) use ($term): void {
            $query->where('customers.name', 'like', $term)
                ->orWhere('customers.whatsapp', 'like', $term)
                ->orWhere('customers.nik', 'like', $term);
        });
    }

    /**
     * Pencarian dari query string, sudah ternormalisasi.
     *
     * Nilai yang panjangnya melebihi kolom dibuang, bukan dibalas 422: daftar
     * penyewa adalah halaman kerja, bukan form.
     *
     * @return array{q: string}
     */
    private function filters(Request $request): array
    {
        $search = $request->query('q');

        return [
            'q' => is_string($search)
                ? Str::limit(trim($search), self::MAX_SEARCH_LENGTH, '')
                : '',
        ];
    }

    /**
     * Satu baris daftar penyewa.
     *
     * @return array<string, mixed>
     */
    private function row(Customer $customer): array
    {
        $total = (int) $customer->total_transaction;

        return [
            'id' => (int) $customer->getKey(),
            'name' => $customer->name,
            'whatsapp' => $customer->whatsapp,
            'nik' => $customer->masked_nik,
            'address' => $customer->address,
            'city' => $customer->city,
            'bookings_count' => (int) $customer->bookings_count,
            'total_transaction' => $total,
            'total_transaction_label' => number_format($total, 0, ',', '.'),
        ];
    }

    /**
     * Data penyewa untuk halaman detail, dengan NIK tetap disamarkan.
     *
     * @return array<string, mixed>
     */
    private function detail(Customer $customer, Business $business): array
    {
        $businessId = $business->getKey();

        $bookingsCount = (int) $customer->bookings()
            ->where('bookings.business_id', $businessId)
            ->count();

        $total = (int) $customer->bookings()
            ->where('bookings.business_id', $businessId)
            ->where('bookings.booking_status', '!=', BookingStatus::Dibatalkan->value)
            ->sum('total');

        // Tanggal booking pertama di unit ini, bukan tanggal baris customer
        // dibuat: penyewa yang booking di unit lain lebih dulu akan terlihat
        // lebih lama mengenal unit ini daripada kenyataannya.
        $firstBookingAt = $customer->bookings()
            ->where('bookings.business_id', $businessId)
            ->min('created_at');

        return [
            'id' => (int) $customer->getKey(),
            'name' => $customer->name,
            'whatsapp' => $customer->whatsapp,
            'email' => $customer->email,
            'nik' => $customer->masked_nik,
            'address' => $customer->address,
            'city' => $customer->city,
            'notes' => $customer->notes,
            'first_booking_at_label' => BookingPeriod::readableDate(
                $firstBookingAt ?? $customer->created_at,
            ),
            'bookings_count' => $bookingsCount,
            'total_transaction' => $total,
            'total_transaction_label' => number_format($total, 0, ',', '.'),
        ];
    }

    /**
     * Riwayat booking penyewa di unit bisnis ini, dari yang paling baru.
     *
     * Booking unit lain tidak ikut, karena riwayat itu bukan urusan admin di
     * sini. Nama dan harga produk tidak dimuat: yang dibutuhkan halaman detail
     * penyewa hanya kode, periode, total, dan statusnya, dan detail lengkapnya
     * sudah ada di halaman booking masing-masing.
     *
     * @return list<array<string, mixed>>
     */
    private function bookings(Customer $customer, Business $business): array
    {
        return array_values(
            $customer->bookings()
                ->where('bookings.business_id', $business->getKey())
                ->orderByDesc('bookings.start_date')
                ->orderByDesc('bookings.id')
                ->get()
                ->map(fn (Booking $booking): array => [
                    'booking_code' => $booking->booking_code,
                    'status' => $booking->booking_status->value,
                    'status_label' => $booking->booking_status->label(),
                    'payment_status' => $booking->payment_status->value,
                    'payment_status_label' => $booking->payment_status->label(),
                    'period_label' => BookingPeriod::readableShortDate($booking->start_date)
                        .' - '.BookingPeriod::readableShortDate($booking->end_date),
                    'total' => (int) $booking->total,
                    'total_label' => number_format((int) $booking->total, 0, ',', '.'),
                    'created_at_label' => BookingPeriod::readableShortDate($booking->created_at),
                ])
                ->all()
        );
    }

    /**
     * Apakah penyewa ini punya booking di unit bisnis tersebut.
     *
     * Dipakai halaman detail untuk menutup customer yang hanya menyewa di unit
     * lain. Nilai ini sengaja dihitung dari booking, bukan dari baris customer,
     * karena barisnya memang tidak terikat unit mana pun.
     */
    private function belongsToBusiness(Customer $customer, Business $business): bool
    {
        return $customer->bookings()
            ->where('bookings.business_id', $business->getKey())
            ->exists();
    }
}
