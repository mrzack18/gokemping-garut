<?php

namespace App\Http\Controllers;

use App\Http\Requests\TicketLookupRequest;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Scopes\BusinessScope;
use App\Support\BookingPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Cek tiket publik (kode booking + nomor WhatsApp).
 *
 * Dipakai penyewa untuk melihat status booking-nya, dan oleh staf untuk
 * memverifikasi tiket saat barang diambil. Karena tidak butuh login, halaman
 * ini tidak boleh membuka tiket hanya dengan kode booking: kode booking
 * berurutan per hari, jadi bisa ditebak. Verifikasinya adalah nomor WhatsApp
 * yang harus cocok dengan penyewa booking tersebut, dan endpoint-nya dibatasi
 * throttle supaya tidak bisa dipakai menebak data orang.
 *
 * NIK tidak pernah ikut di payload tiket; halaman ini milik publik.
 */
class TicketController extends Controller
{
    /**
     * Berapa banyak percobaan cek tiket per menit per alamat IP.
     *
     * Lebih longgar dari lookup biodata karena staf bisa memeriksa banyak
     * tiket berurutan saat jam pengambilan barang.
     */
    public const THROTTLE = '30,1';

    /**
     * Halaman kosong berisi formulir cek tiket.
     */
    public function check(): Response
    {
        return Inertia::render('tickets/check', [
            'ticket' => null,
            'businesses' => $this->activeBusinesses(),
        ]);
    }

    /**
     * Cari tiket berdasarkan kode booking dan nomor WhatsApp penyewanya.
     */
    public function lookup(TicketLookupRequest $request): Response|RedirectResponse
    {
        $whatsapp = $request->normalizedWhatsapp();

        if ($whatsapp === null) {
            return back()->withInput()->withErrors([
                'whatsapp' => 'Nomor WhatsApp tidak valid.',
            ]);
        }

        /**
         * Scope tenancy dinonaktifkan karena halaman ini lintas unit dan tidak
         * butuh login. Kalau admin sedang login, scope akan menyaring tiket
         * unit lain dan halaman cek tiket justru rusak untuk staf.
         */
        $booking = BusinessScope::withoutBusinessScope(
            Booking::query()
                ->with(['business', 'customer', 'items', 'payment'])
                ->where('booking_code', $request->code())
                ->whereHas('customer', fn (Builder $query): Builder => $query->where('whatsapp', $whatsapp))
        )->first();

        if (! $booking instanceof Booking) {
            return back()->withInput()->withErrors([
                'booking_code' => 'Tiket tidak ditemukan. Periksa kembali kode booking dan nomor WhatsApp-nya.',
            ]);
        }

        return Inertia::render('tickets/check', [
            'ticket' => $this->ticket($booking),
            'businesses' => $this->activeBusinesses(),
        ]);
    }

    /**
     * Data tiket yang boleh dilihat publik, tanpa NIK dan tanpa alamat.
     *
     * @return array<string, mixed>
     */
    private function ticket(Booking $booking): array
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

    /**
     * Unit aktif untuk navigasi dan footer halaman publik.
     *
     * @return Collection<int, Business>
     */
    private function activeBusinesses(): Collection
    {
        return Business::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get([
                'id',
                'name',
                'slug',
                'description',
                'service_intro',
                'service_highlights',
                'rental_terms',
                'whatsapp',
                'phone',
                'email',
                'address',
                'maps_embed_url',
            ]);
    }
}
