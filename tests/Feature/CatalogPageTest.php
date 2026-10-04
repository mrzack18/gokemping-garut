<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class CatalogPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_katalog_gokemping_dapat_diakses_tanpa_login(): void
    {
        $this->business('gokemping');

        $this->get(route('catalog.gokemping'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('catalog/index')
                ->has('business')
                ->has('businesses')
                ->has('categories')
                ->has('products')
                ->has('filters')
                ->has('priceBounds')
            );
    }

    public function test_katalog_sepeda_menggunakan_halaman_yang_sama(): void
    {
        $this->business('sewa-sepeda-garut');

        $this->get(route('catalog.sewaSepedaGarut'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('catalog/index')
                ->where('business.slug', 'sewa-sepeda-garut')
            );
    }

    public function test_produk_lintas_unit_tidak_bocor_ke_katalog_lain(): void
    {
        $gokemping = $this->business('gokemping');
        $sepeda = $this->business('sewa-sepeda-garut');

        Product::factory()->forBusiness($gokemping)->create(['name' => 'Tenda Dome']);
        Product::factory()->forBusiness($sepeda)->create(['name' => 'Sepeda MTB']);

        $this->get(route('catalog.gokemping'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('catalog/index')
                ->where('products.total', 1)
                ->where('products.data.0.name', 'Tenda Dome')
            );
    }

    public function test_produk_nonaktif_tidak_muncul(): void
    {
        $business = $this->business('gokemping');

        Product::factory()->forBusiness($business)->create(['name' => 'Produk Aktif']);
        Product::factory()->forBusiness($business)->inactive()->create(['name' => 'Produk Nonaktif']);

        $this->get(route('catalog.gokemping'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('catalog/index')
                ->where('products.total', 1)
                ->where('products.data.0.name', 'Produk Aktif')
            );
    }

    public function test_unit_nonaktif_menghasilkan_404(): void
    {
        $this->business('gokemping', isActive: false);

        $this->get(route('catalog.gokemping'))->assertNotFound();
    }

    public function test_unit_tidak_ada_menghasilkan_404(): void
    {
        $this->get(route('catalog.sewaSepedaGarut'))->assertNotFound();
    }

    public function test_card_produk_menampilkan_kategori_harga_satuan_status_dan_deskripsi(): void
    {
        $business = $this->business('gokemping');
        $category = Category::factory()->forBusiness($business)->create(['name' => 'Tenda']);

        Product::factory()->forBusiness($business)->withCategory($category)->create([
            'name' => 'Tenda Dome 4 Person',
            'description' => 'Tenda dome untuk empat orang.',
            'price' => 75000,
            'price_unit' => 'hari',
            'stock' => 3,
        ]);

        $this->get(route('catalog.gokemping'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('catalog/index')
                ->where('products.data.0.name', 'Tenda Dome 4 Person')
                ->where('products.data.0.category.name', 'Tenda')
                ->where('products.data.0.price', 75000)
                ->where('products.data.0.price_unit', 'hari')
                ->where('products.data.0.stock', 3)
                ->where('products.data.0.is_available', true)
                ->where('products.data.0.description', 'Tenda dome untuk empat orang.')
            );
    }

    public function test_produk_stok_nol_ditandai_tidak_tersedia(): void
    {
        $business = $this->business('gokemping');

        Product::factory()->forBusiness($business)->create([
            'name' => 'Tenda Habis',
            'stock' => 0,
        ]);

        $this->get(route('catalog.gokemping'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('catalog/index')
                ->where('products.data.0.is_available', false)
            );
    }

    public function test_foto_produk_memakai_gambar_utama(): void
    {
        $business = $this->business('gokemping');
        $product = Product::factory()->forBusiness($business)->create();

        ProductImage::factory()->for($product)->create([
            'image' => 'products/biasa.jpg',
            'sort_order' => 1,
        ]);
        ProductImage::factory()->for($product)->primary()->create([
            'image' => 'products/utama.jpg',
            'sort_order' => 2,
        ]);

        $this->get(route('catalog.gokemping'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('catalog/index')
                ->where('products.data.0.photo', url('/storage/products/utama.jpg'))
            );
    }

    public function test_produk_tanpa_foto_menghasilkan_photo_null(): void
    {
        $business = $this->business('gokemping');

        Product::factory()->forBusiness($business)->create();

        $this->get(route('catalog.gokemping'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('catalog/index')
                ->where('products.data.0.photo', null)
            );
    }

    public function test_pencarian_menyaring_nama_produk(): void
    {
        $business = $this->business('gokemping');

        Product::factory()->forBusiness($business)->create(['name' => 'Tenda Dome']);
        Product::factory()->forBusiness($business)->create(['name' => 'Kompor Gas']);

        $this->get(route('catalog.gokemping', ['q' => 'tenda']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('catalog/index')
                ->where('products.total', 1)
                ->where('products.data.0.name', 'Tenda Dome')
                ->where('filters.q', 'tenda')
            );
    }

    public function test_pencarian_mengabaikan_karakter_wildcard_like(): void
    {
        $business = $this->business('gokemping');

        Product::factory()->forBusiness($business)->create(['name' => 'Tenda Dome']);
        Product::factory()->forBusiness($business)->create(['name' => 'Kompor Gas']);

        $this->get(route('catalog.gokemping', ['q' => '%']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('catalog/index')
                ->where('products.total', 0)
            );
    }

    public function test_filter_kategori(): void
    {
        $business = $this->business('gokemping');
        $tenda = Category::factory()->forBusiness($business)->create(['slug' => 'tenda']);
        $sepeda = Category::factory()->forBusiness($business)->create(['slug' => 'sepeda']);

        Product::factory()->forBusiness($business)->withCategory($tenda)->create(['name' => 'Tenda Dome']);
        Product::factory()->forBusiness($business)->withCategory($sepeda)->create(['name' => 'Sepeda Mini']);

        $this->get(route('catalog.gokemping', ['category' => 'tenda']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('catalog/index')
                ->where('products.total', 1)
                ->where('products.data.0.name', 'Tenda Dome')
                ->where('filters.category', 'tenda')
            );
    }

    public function test_filter_kategori_menabaikan_slug_kategori_dari_unit_lain(): void
    {
        $gokemping = $this->business('gokemping');
        $sepeda = $this->business('sewa-sepeda-garut');

        $foreign = Category::factory()->forBusiness($sepeda)->create(['slug' => 'tenda']);

        Product::factory()->forBusiness($gokemping)->create(['name' => 'Tenda Dome']);
        Product::factory()->forBusiness($gokemping)->withCategory($foreign)->create(['name' => 'Tenda Asing']);

        $this->get(route('catalog.gokemping', ['category' => 'tenda']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('catalog/index')
                ->where('products.total', 2)
                ->where('products.data.0.name', 'Tenda Asing')
                ->where('products.data.1.name', 'Tenda Dome')
            );
    }

    public function test_filter_harga_minimal_dan_maksimal(): void
    {
        $business = $this->business('gokemping');

        Product::factory()->forBusiness($business)->create(['name' => 'Murah', 'price' => 25000]);
        Product::factory()->forBusiness($business)->create(['name' => 'Sedang', 'price' => 75000]);
        Product::factory()->forBusiness($business)->create(['name' => 'Mahal', 'price' => 150000]);

        $this->get(route('catalog.gokemping', ['min_price' => 50000, 'max_price' => 100000]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('catalog/index')
                ->where('products.total', 1)
                ->where('products.data.0.name', 'Sedang')
                ->where('filters.min_price', 50000)
                ->where('filters.max_price', 100000)
            );
    }

    public function test_filter_harga_tidak_valid_justru_diabaikan(): void
    {
        $business = $this->business('gokemping');

        Product::factory()->forBusiness($business)->create(['price' => 75000]);

        $this->get(route('catalog.gokemping', ['min_price' => 'abc', 'max_price' => -5]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('catalog/index')
                ->where('products.total', 1)
                ->where('filters.min_price', null)
                ->where('filters.max_price', null)
            );
    }

    public function test_harga_maks_diperbaiki_bila_kurang_dari_harga_min(): void
    {
        $business = $this->business('gokemping');

        Product::factory()->forBusiness($business)->create(['name' => 'Murah', 'price' => 25000]);
        Product::factory()->forBusiness($business)->create(['name' => 'Tepat', 'price' => 100000]);
        Product::factory()->forBusiness($business)->create(['name' => 'Mahal', 'price' => 150000]);

        $this->get(route('catalog.gokemping', ['min_price' => 100000, 'max_price' => 50000]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('catalog/index')
                ->where('filters.min_price', 100000)
                ->where('filters.max_price', 100000)
                ->where('products.total', 1)
                ->where('products.data.0.name', 'Tepat')
            );
    }

    public function test_sorting_harga_terendah(): void
    {
        $business = $this->business('gokemping');

        Product::factory()->forBusiness($business)->create(['name' => 'Mahal', 'price' => 150000]);
        Product::factory()->forBusiness($business)->create(['name' => 'Murah', 'price' => 25000]);
        Product::factory()->forBusiness($business)->create(['name' => 'Sedang', 'price' => 75000]);

        $this->get(route('catalog.gokemping', ['sort' => 'harga_terendah']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('catalog/index')
                ->where('products.data.0.name', 'Murah')
                ->where('products.data.1.name', 'Sedang')
                ->where('products.data.2.name', 'Mahal')
                ->where('filters.sort', 'harga_terendah')
            );
    }

    public function test_sorting_harga_tertinggi(): void
    {
        $business = $this->business('gokemping');

        Product::factory()->forBusiness($business)->create(['name' => 'Murah', 'price' => 25000]);
        Product::factory()->forBusiness($business)->create(['name' => 'Mahal', 'price' => 150000]);

        $this->get(route('catalog.gokemping', ['sort' => 'harga_tertinggi']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('catalog/index')
                ->where('products.data.0.name', 'Mahal')
                ->where('products.data.1.name', 'Murah')
            );
    }

    public function test_sorting_nama_produk(): void
    {
        $business = $this->business('gokemping');

        Product::factory()->forBusiness($business)->create(['name' => 'Zebra Lite']);
        Product::factory()->forBusiness($business)->create(['name' => 'Alpha Tent']);

        $this->get(route('catalog.gokemping', ['sort' => 'nama']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('catalog/index')
                ->where('products.data.0.name', 'Alpha Tent')
                ->where('products.data.1.name', 'Zebra Lite')
            );
    }

    public function test_sorting_terbaru_menggunakan_id_terbesar(): void
    {
        $business = $this->business('gokemping');

        Product::factory()->forBusiness($business)->create(['name' => 'Pertama']);
        $latest = Product::factory()->forBusiness($business)->create(['name' => 'Terbaru']);

        $this->get(route('catalog.gokemping', ['sort' => 'terbaru']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('catalog/index')
                ->where('products.data.0.id', $latest->id)
                ->where('filters.sort', 'terbaru')
            );
    }

    public function test_sorting_tidak_dikenal_kembali_ke_default(): void
    {
        $business = $this->business('gokemping');

        Product::factory()->forBusiness($business)->create();

        $this->get(route('catalog.gokemping', ['sort' => 'acak']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('catalog/index')
                ->where('filters.sort', 'terbaru')
            );
    }

    public function test_pagination_memotong_dua_belas_produk_per_halaman(): void
    {
        $business = $this->business('gokemping');

        Product::factory()->count(15)->forBusiness($business)->create();

        $this->get(route('catalog.gokemping'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('catalog/index')
                ->where('products.total', 15)
                ->where('products.per_page', 12)
                ->where('products.current_page', 1)
                ->where('products.last_page', 2)
                ->has('products.data', 12)
            );

        $this->get(route('catalog.gokemping', ['page' => 2]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('catalog/index')
                ->where('products.current_page', 2)
                ->has('products.data', 3)
            );
    }

    public function test_state_filter_tersimpan_di_url(): void
    {
        $business = $this->business('gokemping');
        $category = Category::factory()->forBusiness($business)->create(['slug' => 'tenda']);

        Product::factory()->forBusiness($business)->withCategory($category)->create([
            'name' => 'Tenda Dome',
            'price' => 75000,
        ]);

        $this->get(route('catalog.gokemping', [
            'q' => 'tenda',
            'category' => 'tenda',
            'min_price' => 50000,
            'max_price' => 100000,
            'sort' => 'harga_terendah',
        ]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('catalog/index')
                ->where('filters', [
                    'q' => 'tenda',
                    'category' => 'tenda',
                    'min_price' => 50000,
                    'max_price' => 100000,
                    'sort' => 'harga_terendah',
                ])
                ->where('products.total', 1)
                ->where('products.current_page', 1)
            );
    }

    public function test_daftar_kategori_hanya_dari_unit_dan_menampilkan_jumlah_produk(): void
    {
        $gokemping = $this->business('gokemping');
        $sepeda = $this->business('sewa-sepeda-garut');

        $tenda = Category::factory()->forBusiness($gokemping)->create(['name' => 'Tenda', 'sort_order' => 1]);
        Category::factory()->forBusiness($gokemping)->inactive()->create(['name' => 'Kategori Mati', 'sort_order' => 2]);
        Category::factory()->forBusiness($sepeda)->create(['name' => 'Sepeda', 'sort_order' => 3]);

        Product::factory()->forBusiness($gokemping)->withCategory($tenda)->create();
        Product::factory()->forBusiness($gokemping)->withCategory($tenda)->create();
        Product::factory()->forBusiness($gokemping)->withCategory($tenda)->inactive()->create();

        $this->get(route('catalog.gokemping'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('catalog/index')
                ->has('categories', 1)
                ->where('categories.0.name', 'Tenda')
                ->where('categories.0.products_count', 2)
            );
    }

    public function test_rentang_harga_dasar_berasal_dari_produk_aktif_unit(): void
    {
        $business = $this->business('gokemping');

        Product::factory()->forBusiness($business)->create(['price' => 25000]);
        Product::factory()->forBusiness($business)->create(['price' => 150000]);
        Product::factory()->forBusiness($business)->inactive()->create(['price' => 999000]);

        $this->get(route('catalog.gokemping'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('catalog/index')
                ->where('priceBounds', ['min' => 25000, 'max' => 150000])
            );
    }

    public function test_admin_yang_login_tetap_melihat_katalog_unit_lain(): void
    {
        $own = $this->business('gokemping');
        $other = $this->business('sewa-sepeda-garut');

        Product::factory()->forBusiness($other)->create(['name' => 'Sepeda MTB']);

        $this->actingAs(User::factory()->forBusiness($own)->create())
            ->get(route('catalog.sewaSepedaGarut'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('catalog/index')
                ->where('business.slug', 'sewa-sepeda-garut')
                ->where('products.total', 1)
                ->where('products.data.0.name', 'Sepeda MTB')
            );
    }

    private function business(string $slug, bool $isActive = true): Business
    {
        return Business::factory()->create([
            'slug' => $slug,
            'is_active' => $isActive,
        ]);
    }
}
