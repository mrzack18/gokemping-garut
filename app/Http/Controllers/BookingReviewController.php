<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Scopes\BusinessScope;
use App\Services\AvailabilityService;
use App\Support\BookingDraft;
use App\Support\BookingPeriod;
use App\Support\BookingRoutes;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Halaman review booking (ROADMAP 3.8, PRD section 15).
 *
 * Halaman ini menampilkan rekap detail booking, data penyewa, dan total,
 * lalu meminta penyewa mengonfirmasi sebelum lanjut ke pembayaran. Halaman
 * pembayaran sendiri dibangun di ROADMAP 3.9, jadi tombolnya masih nonaktif.
 *
 * Ketersediaan dicek ulang di sini (BR-04). Draft sudah divalidasi saat
 * disimpan di ROADMAP 3.5 sampai 3.7, tetapi stok bisa terpakai booking lain
 * di antara langkah tersebut, jadi halaman ini tidak pernah menampilkan total
 * untuk periode yang sudah tidak bisa dipesan tanpa peringatan.
 */
class BookingReviewController extends Controller
{
    /**
     * Field biodata yang wajib terisi sebelum halaman review boleh dibuka.
     *
     * @var list<string>
     */
    private const REQUIRED_CUSTOMER_FIELDS = ['name', 'whatsapp', 'nik', 'address'];

    public function __invoke(
        BookingDraft $draft,
        AvailabilityService $availability,
        string $business,
    ): Response|RedirectResponse {
        $context = $draft->resolveOrFail($business);

        $customer = $this->customerPayload($context['draft']);

        if ($this->customerIsIncomplete($customer)) {
            return to_route('booking.'.BookingRoutes::prefix($business).'.biodata');
        }

        $product = $context['product'];
        $startDate = (string) $context['draft']['start_date'];
        $endDate = (string) $context['draft']['end_date'];
        $quantity = max(1, (int) $context['draft']['quantity']);
        $duration = BookingPeriod::durationInDays($startDate, $endDate);

        $available = $availability->availableUnits($product, $startDate, $endDate);

        return Inertia::render('booking/review', [
            'business' => $context['business'],
            'businesses' => $this->activeBusinesses(),
            'product' => [
                'id' => (int) $product->getKey(),
                'name' => $product->name,
                'slug' => $product->slug,
                'price' => $product->price,
                'price_unit' => $product->price_unit,
                'stock' => $product->stock,
            ],
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'start_date_label' => BookingPeriod::readableDate($startDate),
                'end_date_label' => BookingPeriod::readableDate($endDate),
                'duration' => $duration,
                'duration_label' => $duration.' hari',
                'quantity' => $quantity,
            ],
            'availability' => [
                'available' => $available,
                'requested' => $quantity,
                'is_available' => $available >= $quantity,
            ],
            'pricing' => [
                'price' => (int) $product->price,
                'price_label' => number_format((int) $product->price, 0, ',', '.'),
                'subtotal' => BookingPeriod::total((int) $product->price, $quantity, $duration),
                'total' => BookingPeriod::total((int) $product->price, $quantity, $duration),
            ],
            'customer' => $customer,
            'isBikeRental' => BookingRoutes::isBikeRental($business),
        ]);
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
     * Data penyewa dari draft, dinormalkan ke string supaya komponen frontend
     * tidak perlu memeriksa tipe berulang kali.
     *
     * @param  array<string, mixed>  $draft
     * @return array<string, string>
     */
    private function customerPayload(array $draft): array
    {
        $customer = $draft['customer'] ?? [];

        return [
            'name' => $this->stringOrEmpty($customer['name'] ?? null),
            'whatsapp' => $this->stringOrEmpty($customer['whatsapp'] ?? null),
            'email' => $this->stringOrEmpty($customer['email'] ?? null),
            'nik' => $this->stringOrEmpty($customer['nik'] ?? null),
            'address' => $this->stringOrEmpty($customer['address'] ?? null),
            'city' => $this->stringOrEmpty($customer['city'] ?? null),
            'notes' => $this->stringOrEmpty($customer['notes'] ?? null),
            'renter_count' => $this->stringOrEmpty($customer['renter_count'] ?? null),
        ];
    }

    /**
     * @param  array<string, string>  $customer
     */
    private function customerIsIncomplete(array $customer): bool
    {
        foreach (self::REQUIRED_CUSTOMER_FIELDS as $field) {
            if ($customer[$field] === '') {
                return true;
            }
        }

        return false;
    }

    private function stringOrEmpty(mixed $value): string
    {
        if (is_string($value)) {
            return trim($value);
        }

        if (is_int($value)) {
            return (string) $value;
        }

        return '';
    }
}
