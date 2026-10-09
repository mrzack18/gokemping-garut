<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Category;
use App\Models\Product;
use App\Models\Scopes\BusinessScope;
use App\Services\AvailabilityService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Katalog produk per unit bisnis (ROADMAP 3.3, PRD section 9).
 *
 * Berbeda dengan dashboard admin, katalog adalah halaman publik sehingga
 * BusinessScope harus dinonaktifkan secara eksplisit. Unit bisnis sendiri
 * selalu dibatasi lewat route dan Dicek against is_active supaya unit yang
 * dinonaktifkan atau slug yang tidak dikenal menghasilkan 404, bukan katalog
 * kosong.
 *
 * Seluruh state filter hanya dibaca dari query string. Ini disengaja karena
 * ROADMAP 3.3 mensyaratkan filter bisa di-share lewat URL: pengunjung cukup
 * menyalin address bar lalu filter mereproduksi tampilan yang sama.
 */
class CatalogController extends Controller
{
    private const PER_PAGE = 12;

    private const SORT_TERBARU = 'terbaru';

    private const SORT_HARGA_TERENDAH = 'harga_terendah';

    private const SORT_HARGA_TERTINGGI = 'harga_tertinggi';

    private const SORT_NAMA = 'nama';

    /**
     * @var list<string>
     */
    private const SORTS = [
        self::SORT_TERBARU,
        self::SORT_HARGA_TERENDAH,
        self::SORT_HARGA_TERTINGGI,
        self::SORT_NAMA,
    ];

    private const MAX_SEARCH_LENGTH = 100;

    private const DESCRIPTION_LIMIT = 160;

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
        'booking_code_prefix',
    ];

    public function __invoke(Request $request, AvailabilityService $availability): Response
    {
        $business = $this->resolveBusiness($request);
        $filters = $this->filters($request);

        return Inertia::render('catalog/index', [
            'business' => $business,
            'businesses' => $this->activeBusinesses(),
            'categories' => $this->categories($business),
            'products' => $this->products($business, $filters, $availability),
            'filters' => $filters,
            'priceBounds' => $this->priceBounds($business),
        ]);
    }

    /**
     * Menentukan unit bisnis dari route katalog.
     *
     * Nama route katalog dipetakan ke slug unit lewat route default, jadi
     * halaman ini tidak pernah menerima unit bisnis bebas dari query string.
     */
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

    /**
     * Membersihkan query string menjadi state filter yang aman dan konsisten.
     *
     * Nilai yang tidak valid tidak akan mengembalikan error 422 karena katalog
     * adalah halaman penelusuran: parameter yang aneh lebih baik diabaikan
     * supaya pengunjung tetap melihat katalog penuh.
     *
     * @return array{q: string, category: string|null, min_price: int|null, max_price: int|null, sort: string}
     */
    private function filters(Request $request): array
    {
        $minPrice = $this->nonNegativeInt($request->query('min_price'));
        $maxPrice = $this->nonNegativeInt($request->query('max_price'));

        // min_price lebih besar dari max_price akan menghasilkan daftar kosong
        // yang membingungkan, jadi batas atasnya diaperbaiki ke batas bawah.
        if ($minPrice !== null && $maxPrice !== null && $maxPrice < $minPrice) {
            $maxPrice = $minPrice;
        }

        $category = $request->query('category');
        $sort = $request->query('sort');

        return [
            'q' => $this->searchTerm($request),
            'category' => is_string($category) && $category !== '' ? $category : null,
            'min_price' => $minPrice,
            'max_price' => $maxPrice,
            'sort' => is_string($sort) && in_array($sort, self::SORTS, true)
                ? $sort
                : self::SORT_TERBARU,
        ];
    }

    private function searchTerm(Request $request): string
    {
        $term = $request->query('q');

        if (! is_string($term)) {
            return '';
        }

        return mb_substr(trim($term), 0, self::MAX_SEARCH_LENGTH);
    }

    private function nonNegativeInt(mixed $value): ?int
    {
        if (is_int($value)) {
            $parsed = $value;
        } elseif (is_string($value) && preg_match('/^\d+$/', $value) === 1) {
            $parsed = (int) $value;
        } else {
            return null;
        }

        return $parsed >= 0 ? $parsed : null;
    }

    /**
     * @param  array{q: string, category: string|null, min_price: int|null, max_price: int|null, sort: string}  $filters
     * @return LengthAwarePaginator<int, array{
     *     id: int,
     *     name: string,
     *     slug: string,
     *     description: string|null,
     *     price: int,
     *     price_unit: string,
     *     stock: int,
     *     is_available: bool,
     *     available_now: int,
     *     booked_periods: list<array{period_label: string, quantity: int, status_label: string, is_overdue: bool}>,
     *     booked_periods_count: int,
     *     category: array{id: int, name: string}|null,
     *     photo: string|null
     * }>
     */
    private function products(
        Business $business,
        array $filters,
        AvailabilityService $availability,
    ): LengthAwarePaginator {
        $categoryId = $this->categoryId($business, $filters['category']);

        $query = BusinessScope::withoutBusinessScope(
            Product::query()
                ->where('products.business_id', $business->getKey())
                ->active()
                ->with(['category', 'images'])
        );

        if ($filters['q'] !== '') {
            $query->where('products.name', 'like', '%'.$this->escapeLike($filters['q']).'%');
        }

        if ($categoryId !== null) {
            $query->where('products.category_id', $categoryId);
        }

        if ($filters['min_price'] !== null) {
            $query->where('products.price', '>=', $filters['min_price']);
        }

        if ($filters['max_price'] !== null) {
            $query->where('products.price', '<=', $filters['max_price']);
        }

        $this->applySort($query, $filters['sort']);

        $paginator = $query
            ->paginate(self::PER_PAGE)
            ->withQueryString();
        $productIds = array_values(
            $paginator->getCollection()
                ->map(fn (Product $product): int => (int) $product->getKey())
                ->all(),
        );
        $usedNow = $availability->usedUnitsForProducts(
            (int) $business->getKey(),
            $productIds,
            Carbon::today(),
            Carbon::tomorrow(),
        );
        $bookedPeriods = $availability->bookedPeriodsForProducts(
            (int) $business->getKey(),
            $productIds,
        );

        return $paginator->through(function (Product $product) use ($usedNow, $bookedPeriods): array {
            $id = (int) $product->getKey();
            $periods = $bookedPeriods[$id] ?? [];

            return [
                'id' => $id,
                'name' => $product->name,
                'slug' => $product->slug,
                'description' => $product->description === null
                    ? null
                    : Str::limit($product->description, self::DESCRIPTION_LIMIT),
                'price' => $product->price,
                'price_unit' => $product->price_unit,
                'stock' => $product->stock,
                'is_available' => $product->stock > 0,
                'available_now' => max(0, (int) $product->stock - ($usedNow[$id] ?? 0)),
                'booked_periods' => $periods,
                'booked_periods_count' => count($periods),
                'category' => $product->category === null
                    ? null
                    : [
                        'id' => (int) $product->category->getKey(),
                        'name' => $product->category->name,
                    ],
                'photo' => $this->photo($product),
            ];
        });
    }

    /**
     * Foto utama produk: gambar berflag is_primary, atau gambar pertama
     * menurut urutan sort_order bila tidak ada yang ditandai.
     */
    private function photo(Product $product): ?string
    {
        $primary = $product->images->firstWhere('is_primary', true);

        return ($primary ?? $product->images->first())?->url;
    }

    /**
     * @param  Builder<Product>  $query
     */
    private function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            self::SORT_HARGA_TERENDAH => $query
                ->orderBy('products.price')
                ->orderBy('products.id'),
            self::SORT_HARGA_TERTINGGI => $query
                ->orderByDesc('products.price')
                ->orderBy('products.id'),
            self::SORT_NAMA => $query
                ->orderBy('products.name')
                ->orderBy('products.id'),
            default => $query->orderByDesc('products.id'),
        };
    }

    /**
     * Kategori aktif milik unit tersebut beserta jumlah produk aktifnya.
     *
     * Jumlah produk dihitung lewat query terpisah yang juga melewati
     * BusinessScope supaya admin yang sedang login tetap melihat angka
     * produk unit yang sedang dibuka, bukan unitnya sendiri.
     *
     * @return list<array{id: int, name: string, slug: string, products_count: int}>
     */
    private function categories(Business $business): array
    {
        $categories = BusinessScope::withoutBusinessScope(
            Category::query()
                ->where('categories.business_id', $business->getKey())
                ->where('categories.is_active', true)
                ->orderBy('categories.sort_order')
                ->orderBy('categories.name')
        )->get(['id', 'name', 'slug']);

        $counts = BusinessScope::withoutBusinessScope(
            Product::query()
                ->where('business_id', $business->getKey())
                ->where('is_active', true)
                ->whereNotNull('category_id')
                ->selectRaw('category_id, COUNT(*) as aggregate')
                ->groupBy('category_id')
        )->pluck('aggregate', 'category_id');

        return array_values(
            $categories
                ->map(fn (Category $category): array => [
                    'id' => (int) $category->getKey(),
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'products_count' => (int) ($counts->get($category->getKey()) ?? 0),
                ])
                ->all()
        );
    }

    private function categoryId(Business $business, ?string $slug): ?int
    {
        if ($slug === null) {
            return null;
        }

        $category = BusinessScope::withoutBusinessScope(
            Category::query()
                ->where('business_id', $business->getKey())
                ->where('slug', $slug)
                ->where('is_active', true)
        )->first(['id']);

        return $category === null ? null : (int) $category->getKey();
    }

    /**
     * Rentang harga produk aktif unit tersebut, dipakai sebagai placeholder
     * input filter supaya pengunjung punya acuan angka yang valid.
     *
     * @return array{min: int, max: int}
     */
    private function priceBounds(Business $business): array
    {
        $price = fn (): Builder => BusinessScope::withoutBusinessScope(
            Product::query()
                ->where('business_id', $business->getKey())
                ->active()
        );

        return [
            'min' => (int) $price()->min('price'),
            'max' => (int) $price()->max('price'),
        ];
    }

    /**
     * Meloloskan karakter wildcard LIKE supaya pencarian "50%" tidak berubah
     * menjadi pencarian semua produk.
     */
    private function escapeLike(string $value): string
    {
        return addcslashes($value, '\\%_');
    }
}
