<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethodType;
use App\Http\Requests\SelectBookingPaymentMethodRequest;
use App\Models\Business;
use App\Models\PaymentMethod;
use App\Models\Scopes\BusinessScope;
use App\Services\AvailabilityService;
use App\Support\BookingDraft;
use App\Support\BookingPeriod;
use App\Support\BookingRoutes;
use App\Support\PaymentMethods;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Halaman pembayaran (ROADMAP 3.9, PRD section 16 dan 17).
 *
 * Metode dipilih di halaman review (PRD section 36) lalu disimpan ke draft.
 * Halaman ini menampilkan detail metode yang dipilih beserta total bayanya,
 * dan memverifikasi ulang bahwa draft, data penyewa, dan ketersediaannya
 * masih layak sebelum apa pun ditampilkan.
 *
 * Upload bukti pembayaran belum dibangun di sini. Validasi, preview, dan
 * hapus ganti bukti milik ROADMAP 3.10, jadi QRIS dan transfer pada tahap ini
 * baru menampilkan keterangan bahwa bukti akan diunggah, tanpa input upload.
 *
 * Tombol "Lanjut Pesan via WhatsApp" sudah tampil sesuai PRD section 17, tetapi
 * masih nonaktif karena pembuatannya ada di ROADMAP 3.12.
 */
class BookingPaymentController extends Controller
{
    /**
     * Menyimpan metode pembayaran yang dipilih lalu meneruskan ke halaman
     * pembayarannya.
     */
    public function store(
        SelectBookingPaymentMethodRequest $request,
        BookingDraft $draft,
        AvailabilityService $availability,
        string $business,
    ): RedirectResponse {
        $context = $draft->resolveOrFail($business);
        $method = $request->paymentMethod();

        $prefix = BookingRoutes::prefix($business);

        /**
         * Halaman pembayaran tidak boleh dibuka lewat URL langsung. Draft yang
         * biodata wajibnya belum lengkap dikembalikan ke formulir biodata,
         * bukan ke review, supaya penyewa mengisi datanya lebih dulu.
         */
        if (! $draft->customerIsComplete()) {
            return to_route('booking.'.$prefix.'.biodata');
        }

        if ($method === null) {
            return to_route('booking.'.$prefix.'.review');
        }

        $available = $availability->availableUnits(
            $context['product'],
            (string) $context['draft']['start_date'],
            (string) $context['draft']['end_date'],
        );

        /**
         * Stok bisa terpakai di antara review dan pilihan metode. Alur
         * dikembalikan ke review supaya jadwalnya bisa diperbaiki di tempat,
         * daripada membawa penyewa ke halaman pembayaran yang pasti bermasalah.
         */
        if ($available < max(1, (int) $context['draft']['quantity'])) {
            return to_route('booking.'.$prefix.'.review');
        }

        $draft->merge(['payment_method' => $method->type->value]);

        return to_route('booking.'.$prefix.'.payment.show', [
            'method' => $method->type->value,
        ]);
    }

    /**
     * Halaman pembayaran untuk metode yang dipilih di halaman review.
     */
    public function show(
        Request $request,
        BookingDraft $draft,
        AvailabilityService $availability,
    ): Response|RedirectResponse {
        /**
         * Parameter route dibaca lewat `$request->route()`, bukan lewat suntikan
         * parameter method. Laravel menyuntikkan parameter route secara
         * posisional, dan pada route ini `{method}` berada di URI sedangkan
         * `business` hanya default, sehingga keduanya akan tertukar kalau
         * dibaca dari argumen method.
         */
        $business = $this->routeParameter($request, 'business');
        $method = $this->routeParameter($request, 'method');

        $context = $draft->resolveOrFail($business);

        $prefix = BookingRoutes::prefix($business);

        if (! $draft->customerIsComplete()) {
            return to_route('booking.'.$prefix.'.biodata');
        }

        $selected = $this->resolveSelectedMethod($context['business'], $context['draft']);

        /**
         * Tanpa metode di draft, atau metode yang dipilih sudah tidak aktif
         * atau datanya belum lengkap, pengguna dikembalikan ke review supaya
         * memilih ulang.
         */
        if ($selected === null || $selected->type->value !== $method) {
            return to_route('booking.'.$prefix.'.review');
        }

        $product = $context['product'];
        $startDate = (string) $context['draft']['start_date'];
        $endDate = (string) $context['draft']['end_date'];
        $quantity = max(1, (int) $context['draft']['quantity']);
        $duration = BookingPeriod::durationInDays($startDate, $endDate);
        $available = $availability->availableUnits($product, $startDate, $endDate);

        return Inertia::render('booking/payment', [
            'business' => $context['business'],
            'businesses' => $this->activeBusinesses(),
            'method' => PaymentMethods::toOption($selected),
            'product' => [
                'name' => $product->name,
                'slug' => $product->slug,
            ],
            'period' => [
                'start_date_label' => BookingPeriod::readableDate($startDate),
                'end_date_label' => BookingPeriod::readableDate($endDate),
                'duration_label' => $duration.' hari',
                'quantity' => $quantity,
            ],
            'availability' => [
                'available' => $available,
                'requested' => $quantity,
                'is_available' => $available >= $quantity,
            ],
            'pricing' => [
                'total' => BookingPeriod::total((int) $product->price, $quantity, $duration),
            ],
        ]);
    }

    /**
     * Metode aktif milik unit bisnis ini yang tercatat di draft, atau `null` kalau
     * draft belum punya metode, metodenya sudah tidak aktif, atau datanya belum
     * cukup untuk memandu penyewa menyelesaikan pembayaran.
     *
     * @param  array<string, mixed>  $draft
     */
    private function resolveSelectedMethod(Business $business, array $draft): ?PaymentMethod
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

        return $found !== null && PaymentMethods::isReady($found) ? $found : null;
    }

    /**
     * Nilai parameter route sebagai string non-kosong.
     *
     * Route unit bisnis memakai `defaults('business', ...)`, jadi nilainya
     * selalu ada. Kalau suatu saat route berubah dan nilai default hilang,
     * halaman ini lebih baik berhenti dengan error eksplisit daripada
     * meneruskan slug yang tidak jelas ke query database.
     */
    private function routeParameter(Request $request, string $name): string
    {
        $value = $request->route($name);

        if (! is_string($value) || $value === '') {
            throw new NotFoundHttpException;
        }

        return $value;
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
