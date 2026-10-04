<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Product;
use App\Models\Scopes\BusinessScope;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Halaman pilih layanan (ROADMAP 3.2, PRD section 8).
 *
 * Pengunjung memilih salah satu unit bisnis lalu diarahkan ke katalog unit
 * tersebut. Query produk memakai BusinessScope::withoutBusinessScope() karena
 * halaman ini memang lintas tenant dan harus sama untuk semua pengunjung.
 */
class ServiceSelectionController extends Controller
{
    private const PRODUCTS_PER_BUSINESS = 3;

    /**
     * @var list<string>
     */
    private const PRODUCT_COLUMNS = [
        'id',
        'business_id',
        'category_id',
        'name',
        'price',
        'price_unit',
        'stock',
    ];

    public function __invoke(): Response
    {
        $businesses = $this->businesses();

        return Inertia::render('services/index', [
            'businesses' => $businesses,
            'previewProducts' => $this->previewProducts($businesses),
        ]);
    }

    /**
     * @return Collection<int, Business>
     */
    private function businesses(): Collection
    {
        return Business::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get([
                'id',
                'name',
                'slug',
                'description',
                'whatsapp',
                'address',
                'booking_code_prefix',
            ]);
    }

    /**
     * Contoh produk tiap unit supaya pengunjung punya gambaran isi katalog
     * sebelum memilih layanan.
     *
     * @param  Collection<int, Business>  $businesses
     * @return Collection<int, array{business_id: int, products: EloquentCollection<int, Product>}>
     */
    private function previewProducts(Collection $businesses): Collection
    {
        return $businesses
            ->map(fn (Business $business): array => [
                'business_id' => (int) $business->getKey(),
                'products' => BusinessScope::withoutBusinessScope(
                    Product::query()
                        ->where('business_id', $business->getKey())
                        ->where('is_active', true)
                        ->where('stock', '>', 0)
                        ->latest('id')
                        ->limit(self::PRODUCTS_PER_BUSINESS)
                )->get(self::PRODUCT_COLUMNS),
            ])
            ->values();
    }
}
