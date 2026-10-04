<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Product;
use App\Models\Scopes\BusinessScope;
use Illuminate\Support\Carbon;

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
        $bookingIds = BusinessScope::withoutBusinessScope(
            Booking::query()
                ->select('bookings.id')
                ->where('bookings.business_id', $product->business_id)
                ->overlappingPeriod($start, $end)
                ->holdingStock()
        );

        return (int) BookingItem::query()
            ->where('booking_items.product_id', $product->getKey())
            ->whereIn('booking_items.booking_id', $bookingIds)
            ->sum('booking_items.quantity');
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
