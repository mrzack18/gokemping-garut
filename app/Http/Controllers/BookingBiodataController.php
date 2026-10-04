<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookingCustomerRequest;
use App\Models\Business;
use App\Models\Scopes\BusinessScope;
use App\Support\BookingDraft;
use App\Support\BookingRoutes;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Halaman biodata penyewa (ROADMAP 3.7, PRD section 14).
 *
 * Halaman ini membaca draft yang disimpan `BookingDraftController` pada
 * langkah sebelumnya. Draft yang hilang, milik unit lain, atau produknya sudah
 * tidak aktif akan mengembalikan 404 supaya tidak ada halaman setengah jadi.
 *
 * Penyimpanan ke `customers` dan `bookings` belum dilakukan di sini. Yang
 * disimpan baru data di session, lalu di ROADMAP 3.11 seluruhnya ditulis ke
 * database dalam satu transaksi.
 */
class BookingBiodataController extends Controller
{
    public function show(BookingDraft $draft, string $business): Response
    {
        $context = $draft->resolveOrFail($business);

        return Inertia::render('booking/biodata', [
            'business' => $context['business'],
            'businesses' => $this->activeBusinesses(),
            'product' => [
                'id' => (int) $context['product']->getKey(),
                'name' => $context['product']->name,
                'slug' => $context['product']->slug,
                'price' => $context['product']->price,
                'price_unit' => $context['product']->price_unit,
                'stock' => $context['product']->stock,
            ],
            'draft' => [
                'start_date' => $context['draft']['start_date'] ?? null,
                'end_date' => $context['draft']['end_date'] ?? null,
                'quantity' => (int) ($context['draft']['quantity'] ?? 1),
            ],
            'customer' => $this->customerPayload($context['draft']),
            'isBikeRental' => BookingRoutes::isBikeRental($business),
        ]);
    }

    /**
     * Menyimpan data penyewa ke draft lalu meneruskan ke halaman review.
     *
     * Draft ditulis dengan `merge()` supaya jadwal yang dipilih pada langkah
     * sebelumnya tidak hilang. Setelah biodata tersimpan, penyewa diarahkan ke
     * review (ROADMAP 3.8) sesuai urutan alur PRD section 36.
     */
    public function store(
        StoreBookingCustomerRequest $request,
        BookingDraft $draft,
        string $business,
    ): RedirectResponse {
        $draft->resolveOrFail($business);

        $draft->merge([
            'customer' => $request->customerPayload(BookingRoutes::isBikeRental($business)),
        ]);

        return to_route('booking.'.BookingRoutes::prefix($business).'.review');
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

    /**
     * Nilai form yang tersimpan di draft, supaya muat ulang halaman tidak
     * menghapus apa yang sudah diisi pengguna.
     *
     * @param  array<string, mixed>  $draft
     * @return array<string, mixed>
     */
    private function customerPayload(array $draft): array
    {
        $customer = $draft['customer'] ?? [];

        return [
            'name' => $customer['name'] ?? '',
            'whatsapp' => $customer['whatsapp'] ?? '',
            'email' => $customer['email'] ?? '',
            'nik' => $customer['nik'] ?? '',
            'address' => $customer['address'] ?? '',
            'city' => $customer['city'] ?? '',
            'notes' => $customer['notes'] ?? '',
            'renter_count' => $customer['renter_count'] ?? '',
        ];
    }
}
