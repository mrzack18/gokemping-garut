<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Product;
use App\Models\Scopes\BusinessScope;
use App\Support\BookingDraft;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Form booking (ROADMAP 3.5, PRD section 11).
 *
 * Langkah ini murni memilih jadwal dan menghitung perkiraan harga. Halaman
 * ini sengaja tidak menyimpan apa pun ke database: penyimpanan booking ada di
 * ROADMAP 3.11, pengecekan ketersediaan real-time ada di ROADMAP 3.6, dan
 * biodata penyewa ada di ROADMAP 3.7.
 *
 * Batas jumlah barang memakai `products.stock`. Pengurangan stok oleh
 * booking lain adalah urusan ROADMAP 3.6.
 */
class BookingFormController extends Controller
{
    /**
     * @var list<string>
     */
    private const BUSINESS_COLUMNS = [
        'id',
        'name',
        'slug',
        'description',
        'whatsapp',
        'email',
        'address',
    ];

    /**
     * @var list<string>
     */
    private const PRODUCT_COLUMNS = [
        'id',
        'business_id',
        'category_id',
        'name',
        'slug',
        'price',
        'price_unit',
        'stock',
    ];

    public function __invoke(
        Request $request,
        BookingDraft $draft,
    ): Response {
        $business = $this->resolveBusiness($request);
        $product = $this->resolveProduct($business, $request);

        return Inertia::render('booking/form', [
            'business' => $business,
            'businesses' => $this->activeBusinesses(),
            'product' => [
                'id' => (int) $product->getKey(),
                'name' => $product->name,
                'slug' => $product->slug,
                'price' => $product->price,
                'price_unit' => $product->price_unit,
                'stock' => $product->stock,
                'photo' => $this->photo($product),
                'category' => $product->category === null
                    ? null
                    : [
                        'id' => (int) $product->category->getKey(),
                        'name' => $product->category->name,
                    ],
            ],
            'minDate' => Carbon::today()->toDateString(),
            'initial' => $this->initialValues($draft, $business, $product),
        ]);
    }

    /**
     * Nilai awal form dari draft yang sudah tersimpan, supaya tombol "Kembali"
     * dari halaman biodata tidak menghapus jadwal yang sudah dipilih.
     *
     * @return array{start_date: string, end_date: string, quantity: int}
     */
    private function initialValues(BookingDraft $draft, Business $business, Product $product): array
    {
        $empty = [
            'start_date' => '',
            'end_date' => '',
            'quantity' => 1,
        ];

        $context = $draft->resolveFor($business->slug);

        if ($context === null || $context['product']->is($product) === false) {
            return $empty;
        }

        $values = $draft->read() ?? [];

        $startDate = $values['start_date'] ?? null;
        $endDate = $values['end_date'] ?? null;

        if (! is_string($startDate) || ! is_string($endDate)) {
            return $empty;
        }

        $quantity = $values['quantity'] ?? 1;

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'quantity' => is_int($quantity) ? max(1, $quantity) : 1,
        ];
    }

    private function resolveBusiness(Request $request): Business
    {
        $slug = $request->route('business');

        if (! is_string($slug) || $slug === '') {
            throw new NotFoundHttpException;
        }

        $business = Business::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->first(self::BUSINESS_COLUMNS);

        if ($business === null) {
            throw new NotFoundHttpException;
        }

        return $business;
    }

    private function resolveProduct(Business $business, Request $request): Product
    {
        $slug = $request->route('product');

        if (! is_string($slug) || $slug === '') {
            throw new NotFoundHttpException;
        }

        $product = BusinessScope::withoutBusinessScope(
            Product::query()
                ->where('products.business_id', $business->getKey())
                ->where('products.slug', $slug)
                ->active()
                ->with(['category', 'images'])
        )->first(self::PRODUCT_COLUMNS);

        if ($product === null) {
            throw new NotFoundHttpException;
        }

        return $product;
    }

    /**
     * @return EloquentCollection<int, Business>
     */
    private function activeBusinesses(): EloquentCollection
    {
        return Business::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get(self::BUSINESS_COLUMNS);
    }

    private function photo(Product $product): ?string
    {
        $image =
            $product->images->firstWhere('is_primary', true) ??
            $product->images->first();

        return $image?->url;
    }
}
