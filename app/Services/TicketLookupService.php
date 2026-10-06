<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Scopes\BusinessScope;
use App\Support\BookingPeriod;
use Illuminate\Database\Eloquent\Builder;

/**
 * Pencarian tiket publik dan panel admin (ROADMAP 5.5).
 *
 * Satu tiket hanya terbuka dengan kombinasi kode booking dan nomor WhatsApp
 * penyewanya. Kode booking berurutan per hari dan bisa ditebak, jadi kode saja
 * tidak cukup untuk membuka data booking orang lain.
 *
 * Perbedaan antara pemanggil publik dan admin hanya pada lingkupnya:
 * - Publik (tanpa unit) mencari lintas unit karena halaman landing memang
 *   lintas tenant, dan admin yang kebetulan login tidak boleh kehilangan
 *   kemampuan memeriksa tiket unit lain.
 * - Panel admin menerima unit admin yang login, jadi staf hanya bisa
 *   memverifikasi tiket unitnya sendiri. `BusinessScope` tetap menyaring
 *   sebagai lapisan kedua.
 */
final class TicketLookupService
{
    /**
     * Cari tiket berdasarkan kode booking dan nomor WhatsApp penyewa.
     *
     * @param  string  $code  Kode booking yang sudah dinormalkan ke huruf besar.
     * @param  string  $whatsapp  Nomor WhatsApp dalam format penyimpanan `62...`.
     * @param  Business|null  $business  Unit yang membatasi pencarian, null untuk lintas unit.
     */
    public function find(string $code, string $whatsapp, ?Business $business = null): ?Booking
    {
        $query = $this->query($code, $business)
            ->whereHas('customer', fn (Builder $query): Builder => $query->where('whatsapp', $whatsapp));

        return $query->first();
    }

    /**
     * Cari tiket hanya dengan kode booking.
     *
     * Dipakai jalur pindai QR: token di URL sudah membuktikan tiket ini memang
     * dibawa pemegangnya, jadi nomor WhatsApp tidak perlu diketik lagi.
     *
     * @param  Business|null  $business  Unit yang membatasi pencarian, null untuk lintas unit.
     */
    public function findByCode(string $code, ?Business $business = null): ?Booking
    {
        return $this->query($code, $business)->first();
    }

    /**
     * Query dasar pencarian tiket dengan lingkup yang sesuai pemanggil.
     *
     * @return Builder<Booking>
     */
    private function query(string $code, ?Business $business): Builder
    {
        $query = Booking::query()
            ->with(['business', 'customer', 'items', 'payment'])
            ->where('booking_code', $code);

        if ($business === null) {
            $query = BusinessScope::withoutBusinessScope($query);
        } else {
            $query->where('bookings.business_id', $business->getKey());
        }

        return $query;
    }

    /**
     * Data tiket yang boleh ditampilkan, tanpa NIK dan tanpa alamat.
     *
     * Dipakai halaman publik maupun panel admin; keduanya tidak memerlukan
     * NIK. Panel admin punya halaman penyewa sendiri untuk data lengkap.
     *
     * @return array<string, mixed>
     */
    public function payload(Booking $booking): array
    {
        $business = $booking->business;
        $customer = $booking->customer;

        return [
            'booking_code' => (string) $booking->booking_code,
            'business' => [
                'name' => $business instanceof Business ? $business->name : '-',
                'whatsapp' => $business instanceof Business ? $business->whatsapp : null,
            ],
            'customer_name' => $customer instanceof Customer ? $customer->name : '-',
            'status' => $booking->booking_status->value,
            'status_label' => $booking->booking_status->label(),
            'payment_status' => $booking->payment_status->value,
            'payment_status_label' => $booking->payment_status->label(),
            'payment_method_label' => $booking->payment_method->label(),
            'items' => array_values(
                $booking->items
                    ->map(fn (BookingItem $item): array => [
                        'product_name' => $item->product_name,
                        'quantity' => (int) $item->quantity,
                        'subtotal_label' => number_format((int) $item->subtotal, 0, ',', '.'),
                    ])
                    ->all()
            ),
            'period' => [
                'start_date_label' => BookingPeriod::readableDate($booking->start_date),
                'end_date_label' => BookingPeriod::readableDate($booking->end_date),
                'total_days_label' => ((int) $booking->total_days).' hari',
            ],
            'total' => (int) $booking->total,
            'total_label' => number_format((int) $booking->total, 0, ',', '.'),
            'cancellation_reason' => $booking->cancellation_reason,
            'created_at_label' => BookingPeriod::readableDate($booking->created_at),
        ];
    }
}
