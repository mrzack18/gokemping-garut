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

class ProductDetailPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_detail_produk_dapat_diakses_tanpa_login(): void
    {
        $business = $this->business('gokemping');
        $product = $this->product($business);

        $this->get(route('catalog.gokemping.show', ['product' => $product->slug]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('catalog/show')
                ->where('business.slug', 'gokemping')
                ->has('businesses')
                ->has('product')
            );
    }

    public function test_detail_produk_menampilkan_seluruh_informasi_prd_section_10(): void
    {
        $business = $this->business('gokemping');
        $category = Category::factory()->forBusiness($business)->create([
            'name' => 'Tenda',
            'slug' => 'tenda',
        ]);

        $product = $this->product($business, [
            'category_id' => $category->id,
            'name' => 'Tenda Dome 4 Person',
            'description' => 'Tenda dome untuk empat orang.',
            'specification' => ['Kapasitas' => '4 orang', 'Berat' => '3200 gram'],
            'rental_terms' => 'Dikembalikan dalam kondisi bersih dan utuh.',
            'price' => 75000,
            'price_unit' => 'hari',
            'stock' => 3,
        ]);

        $this->get(route('catalog.gokemping.show', ['product' => $product->slug]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('catalog/show')
                ->where('product.name', 'Tenda Dome 4 Person')
                ->where('product.category.name', 'Tenda')
                ->where('product.category.slug', 'tenda')
                ->where('product.price', 75000)
                ->where('product.price_unit', 'hari')
                ->where('product.description', 'Tenda dome untuk empat orang.')
                ->where('product.specification', [
                    'Kapasitas' => '4 orang',
                    'Berat' => '3200 gram',
                ])
                ->where('product.rental_terms', 'Dikembalikan dalam kondisi bersih dan utuh.')
                ->where('product.stock', 3)
                ->where('product.is_available', true)
            );
    }

    public function test_produk_tanpa_kategori_menghasilkan_kategori_null(): void
    {
        $business = $this->business('gokemping');
        $product = $this->product($business, ['category_id' => null]);

        $this->get(route('catalog.gokemping.show', ['product' => $product->slug]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('catalog/show')
                ->where('product.category', null)
            );
    }

    public function test_produk_tanpa_spesifikasi_dan_ketentuan_mengembalikan_nilai_kosong(): void
    {
        $business = $this->business('gokemping');
        $product = $this->product($business, [
            'specification' => null,
            'rental_terms' => null,
            'description' => null,
        ]);

        $this->get(route('catalog.gokemping.show', ['product' => $product->slug]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('catalog/show')
                ->where('product.specification', [])
                ->where('product.rental_terms', null)
                ->where('product.description', null)
            );
    }

    public function test_stok_nol_ditandai_tidak_tersedia(): void
    {
        $business = $this->business('gokemping');
        $product = $this->product($business, ['stock' => 0]);

        $this->get(route('catalog.gokemping.show', ['product' => $product->slug]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('catalog/show')
                ->where('product.stock', 0)
                ->where('product.is_available', false)
            );
    }

    public function test_gallery_menampilkan_semua_foto(): void
    {
        $business = $this->business('gokemping');
        $product = $this->product($business);

        ProductImage::factory()->for($product)->create([
            'image' => 'products/satu.jpg',
            'is_primary' => false,
            'sort_order' => 1,
        ]);
        ProductImage::factory()->for($product)->create([
            'image' => 'products/dua.jpg',
            'is_primary' => false,
            'sort_order' => 2,
        ]);

        $this->get(route('catalog.gokemping.show', ['product' => $product->slug]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('catalog/show')
                ->has('product.images', 2)
                ->where('product.images.0.url', url('/storage/products/satu.jpg'))
                ->where('product.images.1.url', url('/storage/products/dua.jpg'))
            );
    }

    public function test_foto_utama_ditempatkan_di_urutan_pertama(): void
    {
        $business = $this->business('gokemping');
        $product = $this->product($business);

        ProductImage::factory()->for($product)->create([
            'image' => 'products/biasa.jpg',
            'is_primary' => false,
            'sort_order' => 1,
        ]);
        ProductImage::factory()->for($product)->primary()->create([
            'image' => 'products/utama.jpg',
            'sort_order' => 5,
        ]);

        $this->get(route('catalog.gokemping.show', ['product' => $product->slug]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('catalog/show')
                ->has('product.images', 2)
                ->where('product.images.0.url', url('/storage/products/utama.jpg'))
                ->where('product.images.0.is_primary', true)
                ->where('product.images.1.url', url('/storage/products/biasa.jpg'))
            );
    }

    public function test_produk_tanpa_foto_menghasilkan_gallery_kosong(): void
    {
        $business = $this->business('gokemping');
        $product = $this->product($business);

        $this->get(route('catalog.gokemping.show', ['product' => $product->slug]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('catalog/show')
                ->has('product.images', 0)
            );
    }

    public function test_slug_yang_sama_di_unit_berbeda_tidak_mencampur(): void
    {
        $gokemping = $this->business('gokemping');
        $sepeda = $this->business('sewa-sepeda-garut');

        $this->product($gokemping, ['slug' => 'tenda-dome', 'name' => 'Tenda Milik GoKemping']);
        $this->product($sepeda, ['slug' => 'tenda-dome', 'name' => 'Tenda Milik Sepeda']);

        $this->get(route('catalog.gokemping.show', ['product' => 'tenda-dome']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('catalog/show')
                ->where('business.slug', 'gokemping')
                ->where('product.name', 'Tenda Milik GoKemping')
            );

        $this->get(route('catalog.sewaSepedaGarut.show', ['product' => 'tenda-dome']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('catalog/show')
                ->where('business.slug', 'sewa-sepeda-garut')
                ->where('product.name', 'Tenda Milik Sepeda')
            );
    }

    public function test_produk_unit_lain_tidak_bisa_diakses_lewat_path_unit_ini(): void
    {
        $gokemping = $this->business('gokemping');
        $sepeda = $this->business('sewa-sepeda-garut');

        $this->product($gokemping);
        $foreign = $this->product($sepeda, ['slug' => 'sepeda-mtb']);

        $this->get(route('catalog.gokemping.show', ['product' => $foreign->slug]))
            ->assertNotFound();
    }

    public function test_produk_nonaktif_menghasilkan_404(): void
    {
        $business = $this->business('gokemping');
        $product = $this->product($business);
        $product->update(['is_active' => false]);

        $this->get(route('catalog.gokemping.show', ['product' => $product->slug]))
            ->assertNotFound();
    }

    public function test_produk_terhapus_menghasilkan_404(): void
    {
        $business = $this->business('gokemping');
        $product = $this->product($business);
        $product->delete();

        $this->get(route('catalog.gokemping.show', ['product' => $product->slug]))
            ->assertNotFound();
    }

    public function test_slug_tak_ada_menghasilkan_404(): void
    {
        $this->business('gokemping');

        $this->get(route('catalog.gokemping.show', ['product' => 'tidak-ada']))
            ->assertNotFound();
    }

    public function test_unit_nonaktif_menghasilkan_404(): void
    {
        $business = $this->business('gokemping', isActive: false);
        $product = $this->product($business);

        $this->get(route('catalog.gokemping.show', ['product' => $product->slug]))
            ->assertNotFound();
    }

    public function test_admin_yang_login_tetap_bisa_membuka_detail_unit_lain(): void
    {
        $own = $this->business('gokemping');
        $other = $this->business('sewa-sepeda-garut');

        $this->product($own);
        $foreign = $this->product($other, ['name' => 'Sepeda MTB']);

        $this->actingAs(User::factory()->forBusiness($own)->create())
            ->get(route('catalog.sewaSepedaGarut.show', ['product' => $foreign->slug]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('catalog/show')
                ->where('product.name', 'Sepeda MTB')
            );
    }

    private function business(string $slug, bool $isActive = true): Business
    {
        return Business::factory()->create([
            'slug' => $slug,
            'is_active' => $isActive,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function product(Business $business, array $attributes = []): Product
    {
        return Product::factory()->forBusiness($business)->create($attributes);
    }
}
