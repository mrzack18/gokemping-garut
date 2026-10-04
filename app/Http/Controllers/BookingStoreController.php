<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethodType;
use App\Exceptions\InsufficientStockException;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Business;
use App\Models\Product;
use App\Models\Scopes\BusinessScope;
use App\Services\BookingService;
use App\Support\BookingDraft;
use App\Support\BookingPeriod;
use App\Support\BookingReceipt;
use App\Support\BookingRoutes;
use App\Support\PaymentMethods;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Penyimpanan booking final (ROADMAP 3.11, PRD section 18).
 *
 * Halaman pembayaran (ROADMAP 3.9 dan 3.10) hanya menyiapkan pilihan metode dan
 * bukti pembayaran di draft. Penyimpanan ke database baru terjadi di sini,
 * saat penyewa menekan tombol konfirmasi.
 *
 * Draft dihapus setelah booking tersimpan supaya penyewa tidak bisa membuat
 * booking kedua dari halaman yang sama hanya dengan menekan tombol lagi.
 * Submit ulang setelah draft hilang berakhir dengan 404, bukan booking ganda.
 */
class BookingStoreController extends Controller
{
    /**
     * Simpan booking, item, dan pembayarannya dalam satu transaksi.
     */
    public function store(
        BookingDraft $draft,
        BookingService $bookings,
        BookingReceipt $receipt,
        string $business,
    ): RedirectResponse {
        $context = $draft->resolveOrFail($business);
        $prefix = BookingRoutes::prefix($business);

        if (! $draft->customerIsComplete()) {
            return to_route('booking.'.$prefix.'.biodata');
        }

        /**
         * Tanpa metode di draft, atau metodenya sudah tidak aktif, booking
         * tidak boleh dibuat diam-diam dengan metode yang berbeda dari yang
         * dipesan penyewa.
         */
        $method = $this->resolveSelectedMethod($context['business'], $context['draft']);

        if ($method === null) {
            return to_route('booking.'.$prefix.'.review');
        }

        /**
         * QRIS dan transfer mewajibkan bukti. Pemeriksaan diulang di sini,
         * bukan hanya di form request unggahan, supaya halaman konfirmasi
         * tetap aman kalau dipanggil tanpa berkas.
         */
        if ($method->requiresProof() && ! is_string($context['draft']['payment_proof'] ?? null)) {
            return to_route('booking.'.$prefix.'.payment.show', [
                'method' => $method->value,
            ])->withErrors([
                'proof' => 'Bukti pembayaran wajib diunggah sebelum booking disimpan.',
            ]);
        }

        try {
            $booking = $bookings->store($context['business'], $context['product'], $context['draft']);
        } catch (InsufficientStockException) {
            /**
             * Stok habis setelah penyewa menyelesaikan pembayaran. Draft tetap
             * disimpan supaya penyewa bisa mengganti periode tanpa mengulang
             * biodata, dan bukti pembayaran yang sudah diunggah tidak hilang.
             */
            return to_route('booking.'.$prefix.'.review')->withErrors([
                'period' => 'Stok untuk periode ini sudah tidak tersedia. Pilih tanggal lain.',
            ]);
        }

        $receipt->write($this->receiptPayload($booking, $context));

        /**
         * Draft sudah tidak dibutuhkan. Berkas bukti tidak dihapus dari disk
         * karena sekarang path-nya dimiliki record `payments`.
         */
        $draft->forget();

        return to_route('booking.'.$prefix.'.success');
    }

    /**
     * Halaman konfirmasi booking.
     *
     * Isinya dibaca dari session, bukan dari URL, supaya kode booking dan data
     * penyewa tidak bisa diakses orang lain dengan menebak alamat halaman.
     */
    public function success(BookingReceipt $receipt, string $business): Response|RedirectResponse
    {
        $data = $receipt->read();

        if ($data === null || ($data['business_slug'] ?? null) !== $business) {
            return to_route('home');
        }

        return Inertia::render('booking/success', [
            'receipt' => $data,
            'businesses' => $this->activeBusinesses(),
        ]);
    }

    /**
     * @param  array{business: Business, product: Product, draft: array<string, mixed>}  $context
     * @return array<string, mixed>
     */
    private function receiptPayload(Booking $booking, array $context): array
    {
        $booking->loadMissing('items');

        $item = $booking->items->first();

        return [
            'booking_code' => $booking->booking_code,
            'business_slug' => $context['business']->slug,
            'business_name' => $context['business']->name,
            'product_name' => $item instanceof BookingItem
                ? $item->product_name
                : $context['product']->name,
            'period' => [
                'start_date_label' => BookingPeriod::readableDate((string) $context['draft']['start_date']),
                'end_date_label' => BookingPeriod::readableDate((string) $context['draft']['end_date']),
                'duration' => (int) $booking->total_days,
                'quantity' => (int) $booking->items->sum('quantity'),
            ],
            'total' => (int) $booking->total,
            'total_label' => number_format((int) $booking->total, 0, ',', '.'),
            'payment' => [
                'method_label' => $booking->payment_method->label(),
                'status_label' => $booking->payment_status->label(),
            ],
            'booking_status_label' => $booking->booking_status->label(),
        ];
    }

    /**
     * Metode dari draft yang masih aktif dan siap dipakai penyewa.
     *
     * @param  array<string, mixed>  $draft
     */
    private function resolveSelectedMethod(Business $business, array $draft): ?PaymentMethodType
    {
        $type = $draft['payment_method'] ?? null;

        if (! is_string($type)) {
            return null;
        }

        $method = PaymentMethodType::tryFrom($type);

        if ($method === null) {
            return null;
        }

        $found = PaymentMethods::find($business, $method);

        return $found !== null && PaymentMethods::isReady($found) ? $method : null;
    }

    /**
     * @return EloquentCollection<int, Business>
     */
    private function activeBusinesses(): EloquentCollection
    {
        return BusinessScope::withoutBusinessScope(
            Business::query()->where('is_active', true)->orderBy('id'),
        )->get(['id', 'name', 'slug', 'description', 'whatsapp', 'email', 'address']);
    }
}
