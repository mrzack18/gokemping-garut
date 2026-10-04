<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Scopes\BusinessScope;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Detail produk per unit bisnis (ROADMAP 3.4, PRD section 10).
 *
 * Produk dicari per pasangan unit bisnis dan slug, bukan lewat route model
 * binding global. Pairing ini wajib karena `products` hanya punya unique key
 * per unit (`business_id`, `slug`), sehingga slug yang sama bisa ada di dua
 * unit dan binding global akan membuka produk dari unit yang salah.
 */
class ProductDetailController extends Controller
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
        'booking_code_prefix',
    ];

    public function __invoke(Request $request): Response
    {
        $business = $this->resolveBusiness($request);
        $product = $this->resolveProduct($business, $request);

        return Inertia::render('catalog/show', [
            'business' => $business,
            'businesses' => $this->activeBusinesses(),
            'product' => [
                'id' => (int) $product->getKey(),
                'name' => $product->name,
                'slug' => $product->slug,
                'description' => $product->description,
                'specification' => $product->specification ?? [],
                'rental_terms' => $product->rental_terms,
                'price' => $product->price,
                'price_unit' => $product->price_unit,
                'stock' => $product->stock,
                'is_available' => $product->stock > 0,
                'category' => $product->category === null
                    ? null
                    : [
                        'id' => (int) $product->category->getKey(),
                        'name' => $product->category->name,
                        'slug' => $product->category->slug,
                    ],
                'images' => $this->images($product),
            ],
        ]);
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
        )->first();

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

    /**
     * Gallery produk. Foto utama selalu ditempatkan lebih dulu supaya foto
     * yang paling mewakili produk tampil sebelum pengunjung menekan tombol
     * navigasi, sisanya mengikuti urutan `sort_order`.
     *
     * Pengurutan dilakukan dua tahap dan urutannya penting: Collection::sortBy()
     * bersifat stabil, jadi sortir per sort_order dulu, baru menaruh foto utama
     * ke depan. Jika dibalik, sortir sort_order akan menyusun ulang semua foto
     * dan foto utama kembali ke posisi semula.
     *
     * @return list<array{url: string, is_primary: bool}>
     */
    private function images(Product $product): array
    {
        $images = $product->images
            ->sortBy(fn (ProductImage $image): int => $image->sort_order)
            ->sortByDesc(fn (ProductImage $image): int => $image->is_primary ? 1 : 0)
            ->values();

        return array_values(
            $images
                ->map(fn (ProductImage $image): array => [
                    'url' => $image->url,
                    'is_primary' => $image->is_primary,
                ])
                ->all()
        );
    }
}
