<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Product;
use App\Models\Scopes\BusinessScope;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Landing page publik (ROADMAP 3.1).
 *
 * Halaman ini menampilkan unit bisnis dan produk unggulan dari seluruh unit,
 * jadi global scope BusinessScope harus dinonaktifkan secara eksplisit.
 * Tanpa itu, admin yang sedang login hanya akan melihat produk unitnya sendiri
 * di halaman yang seharusnya sama untuk semua pengunjung.
 */
class LandingController extends Controller
{
    /**
     * Jumlah produk unggulan per unit bisnis supaya tiap unit sama-sama
     * terlihat di landing page.
     */
    private const FEATURED_PER_BUSINESS = 4;

    /**
     * Kolom produk yang dikirim ke frontend. `business_id` dan `category_id`
     * wajib ikut agar relasi eager load dapat dihidrasi.
     *
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

    public function __invoke(): Response
    {
        $businesses = $this->businesses();

        return Inertia::render('welcome', [
            'businesses' => $businesses,
            'featuredProducts' => $this->featuredProducts($businesses),
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
            ->get(['id', 'name', 'slug', 'description', 'whatsapp', 'email', 'address']);
    }

    /**
     * Produk unggulan dari seluruh unit bisnis aktif.
     *
     * Setiap unit/business_id mengambil FEATURED_PER_BUSINESS produk terbaru
     * supaya unit dengan katalog lebih besar tidak menutupi unit lain.
     * Scope dinonaktifkan per unit karena halaman ini memang lintas tenant.
     *
     * @param  Collection<int, Business>  $businesses
     * @return Collection<int, Product>
     */
    private function featuredProducts(Collection $businesses): Collection
    {
        return $businesses
            ->flatMap(fn (Business $business) => BusinessScope::withoutBusinessScope(
                Product::query()
                    ->where('business_id', $business->getKey())
                    ->where('is_active', true)
                    ->where('stock', '>', 0)
                    ->with(['business:id,name,slug', 'category:id,name'])
                    ->latest('id')
                    ->limit(self::FEATURED_PER_BUSINESS)
            )->get(self::PRODUCT_COLUMNS))
            ->values();
    }
}
