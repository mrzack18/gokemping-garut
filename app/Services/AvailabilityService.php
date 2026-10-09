<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Product;
use App\Models\Scopes\BusinessScope;
use App\Support\BookingPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Perhitungan ketersediaan barang untuk satu periode sewa (BR-04).
 *
 * PRD section 39.3 menyebut `AvailabilityService` dijalankan di dalam
 * `DB::transaction` + `lockForUpdate`. Kunci baris itu belum dipakai di sini
 * karena service ini hanya dibaca pada endpoint pengecekan ketersediaan yang
 * dipanggil setiap kali tanggal berubah. Mengunci baris pada pembacaan
 * publik hanya menambah konteni tanpa mencegah apa pun. Pengaman race yang
 * sesungguhnya adalah saat penyimpanan booking di ROADMAP 3.11, di dalam
 * transaksi dan tepat sebelum stok dikurangi.
 */
class AvailabilityService
{
    /**
     * Unit yang masih bisa disewa untuk produk dan periode tertentu.
     *
     * @param  Carbon|string  $start  Tanggal mulai sewa.
     * @param  Carbon|string  $end  Tanggal selesai sewa, diperlakukan sebagai batas pengembalian.
     */
    public function availableUnits(Product $product, Carbon|string $start, Carbon|string $end): int
    {
        return max(0, $product->stock - $this->usedUnits($product, $start, $end));
    }

    /**
     * Unit yang sudah menahan stok pada periode tersebut.
     *
     * Booking yang sudah selesai atau dibatalkan tidak dihitung, mengikuti
     * `BookingStatus::holdsStock()`.
     */
    public function usedUnits(Product $product, Carbon|string $start, Carbon|string $end): int
    {
        $used = $this->usedUnitsForProducts(
            (int) $product->business_id,
            [(int) $product->getKey()],
            $start,
            $end,
        );

        return $used[(int) $product->getKey()] ?? 0;
    }

    /**
     * Unit yang sudah menahan stok untuk beberapa produk sekaligus.
     *
     * Catalog memakai batch ini supaya halaman dengan beberapa produk tidak
     * menjalankan satu query stok per kartu. Booking yang masih berstatus
     * `sedang_disewa` juga tetap menahan unit yang sudah terlambat dikembalikan,
     * sampai admin mencatat status selesai.
     *
     * @param  list<int>  $productIds
     * @return array<int, int> Peta `product_id => unit terpakai`.
     */
    public function usedUnitsForProducts(
        int $businessId,
        array $productIds,
        Carbon|string $start,
        Carbon|string $end,
    ): array {
        if ($productIds === []) {
            return [];
        }

        $includeOverdueRentals = Carbon::parse($start)->startOfDay()
            ->greaterThanOrEqualTo(Carbon::today()->startOfDay());

        $bookingQuery = Booking::query()
            ->where('bookings.business_id', $businessId)
            ->where(function (Builder $query) use ($start, $end, $includeOverdueRentals): void {
                $query->overlappingPeriod($start, $end);

                if ($includeOverdueRentals) {
                    $query->orWhere(function (Builder $overdue): void {
                        $overdue
                            ->where('bookings.booking_status', BookingStatus::SedangDisewa->value)
                            ->whereDate('bookings.end_date', '<=', Carbon::today()->toDateString());
                    });
                }
            })
            ->holdingStock()
            ->select('bookings.id');

        $bookingIds = BusinessScope::withoutBusinessScope($bookingQuery);

        $rows = DB::table('booking_items')
            ->whereIn('booking_items.product_id', $productIds)
            ->whereIn('booking_items.booking_id', $bookingIds)
            ->select('booking_items.product_id')
            ->selectRaw('SUM(booking_items.quantity) as used_units')
            ->groupBy('booking_items.product_id')
            ->get();

        $used = [];

        foreach ($rows as $row) {
            $used[(int) $row->product_id] = (int) $row->used_units;
        }

        return $used;
    }

    /**
     * Periode booking yang saat ini masih menahan stok, dikelompokkan per
     * produk. Data ini aman ditampilkan di katalog karena hanya berisi periode,
     * jumlah unit, dan status—tidak ada data pribadi penyewa.
     *
     * @param  list<int>  $productIds
     * @return array<int, list<array{period_label: string, quantity: int, status_label: string, is_overdue: bool}>>
     */
    public function bookedPeriodsForProducts(int $businessId, array $productIds): array
    {
        if ($productIds === []) {
            return [];
        }

        $holdingStatuses = array_map(
            fn (BookingStatus $status): string => $status->value,
            array_values(array_filter(
                BookingStatus::cases(),
                fn (BookingStatus $status): bool => $status->holdsStock(),
            )),
        );

        $rows = DB::table('booking_items')
            ->join('bookings', 'booking_items.booking_id', '=', 'bookings.id')
            ->where('bookings.business_id', $businessId)
            ->whereIn('bookings.booking_status', $holdingStatuses)
            ->where(function (QueryBuilder $query): void {
                $query->whereDate('bookings.end_date', '>=', Carbon::today()->toDateString())
                    ->orWhere('bookings.booking_status', BookingStatus::SedangDisewa->value);
            })
            ->whereIn('booking_items.product_id', $productIds)
            ->orderBy('bookings.start_date')
            ->orderBy('bookings.id')
            ->get([
                'booking_items.product_id as product_id',
                'booking_items.quantity as quantity',
                'bookings.start_date as start_date',
                'bookings.end_date as end_date',
                'bookings.booking_status as booking_status',
            ]);

        $periods = [];
        $today = Carbon::today()->toDateString();

        foreach ($rows as $row) {
            $status = BookingStatus::from((string) $row->booking_status);
            $startDate = (string) $row->start_date;
            $endDate = (string) $row->end_date;

            $periods[(int) $row->product_id][] = [
                'period_label' => BookingPeriod::readableDate($startDate)
                    .' – '.BookingPeriod::readableDate($endDate),
                'quantity' => (int) $row->quantity,
                'status_label' => $status->label(),
                'is_overdue' => $status === BookingStatus::SedangDisewa && $endDate < $today,
            ];
        }

        return $periods;
    }

    /**
     * Ringkasan ketersediaan untuk ditampilkan pada form booking.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function summarise(Product $product, Carbon|string $start, Carbon|string $end, array $attributes = []): array
    {
        $used = $this->usedUnits($product, $start, $end);
        $available = max(0, $product->stock - $used);
        $requested = (int) ($attributes['requested'] ?? 1);

        return [
            'product_id' => (int) $product->getKey(),
            'start_date' => Carbon::parse($start)->toDateString(),
            'end_date' => Carbon::parse($end)->toDateString(),
            'stock' => (int) $product->stock,
            'used' => $used,
            'available' => $available,
            'requested' => $requested,
            'is_available' => $requested <= $available,
        ];
    }
}
