<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Business;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class BookingFormPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_form_booking_dapat_diakses_tanpa_login(): void
    {
        $business = $this->business('gokemping');
        $product = $this->product($business);

        $this->get(route('booking.gokemping.create', ['product' => $product->slug]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('booking/form')
                ->where('business.slug', 'gokemping')
                ->has('businesses')
                ->has('product')
                ->has('minDate')
            );
    }

    public function test_form_booking_tersedia_di_kedua_unit(): void
    {
        $gokemping = $this->business('gokemping');
        $sepeda = $this->business('sewa-sepeda-garut');

        $tenda = $this->product($gokemping);
        $sepedaProduct = $this->product($sepeda);

        $this->get(route('booking.gokemping.create', ['product' => $tenda->slug]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('booking/form')
                ->where('business.slug', 'gokemping')
                ->where('product.name', $tenda->name)
            );

        $this->get(route('booking.sewaSepedaGarut.create', ['product' => $sepedaProduct->slug]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('booking/form')
                ->where('business.slug', 'sewa-sepeda-garut')
                ->where('product.name', $sepedaProduct->name)
            );
    }

    public function test_data_produk_dikirim_lengkap_untuk_kalkulator_harga(): void
    {
        $business = $this->business('gokemping');
        $category = Category::factory()->forBusiness($business)->create([
            'name' => 'Tenda',
            'slug' => 'tenda',
        ]);

        $product = $this->product($business, [
            'category_id' => $category->id,
            'name' => 'Tenda Dome 4 Person',
            'slug' => 'tenda-dome-4-person',
            'price' => 75000,
            'price_unit' => 'hari',
            'stock' => 5,
        ]);

        $this->get(route('booking.gokemping.create', ['product' => $product->slug]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('booking/form')
                ->where('product.id', $product->id)
                ->where('product.name', 'Tenda Dome 4 Person')
                ->where('product.slug', 'tenda-dome-4-person')
                ->where('product.price', 75000)
                ->where('product.price_unit', 'hari')
                ->where('product.stock', 5)
                ->where('product.category.id', $category->id)
                ->where('product.category.name', 'Tenda')
            );
    }

    public function test_batas_tanggal_minimal_adalah_hari_ini(): void
    {
        $business = $this->business('gokemping');
        $product = $this->product($business);

        $this->get(route('booking.gokemping.create', ['product' => $product->slug]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('booking/form')
                ->where('minDate', now()->toDateString())
            );
    }

    public function test_produk_tanpa_kategori_dan_foto_menghasilkan_nilai_kosong(): void
    {
        $business = $this->business('gokemping');
        $product = $this->product($business, ['category_id' => null]);

        $this->get(route('booking.gokemping.create', ['product' => $product->slug]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('booking/form')
                ->where('product.category', null)
                ->where('product.photo', null)
            );
    }

    public function test_foto_utama_digunakan_untuk_ringkasan_produk(): void
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

        $this->get(route('booking.gokemping.create', ['product' => $product->slug]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('booking/form')
                ->where('product.photo', url('/storage/products/utama.jpg'))
            );
    }

    public function test_produk_stok_nol_tetap_dirender_untuk_isi_pesan(): void
    {
        $business = $this->business('gokemping');
        $product = $this->product($business, ['stock' => 0]);

        $this->get(route('booking.gokemping.create', ['product' => $product->slug]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('booking/form')
                ->where('product.stock', 0)
            );
    }

    public function test_slug_yang_sama_di_unit_berbeda_tidak_mencampur(): void
    {
        $gokemping = $this->business('gokemping');
        $sepeda = $this->business('sewa-sepeda-garut');

        $this->product($gokemping, [
            'slug' => 'tenda-dome',
            'name' => 'Tenda Milik GoKemping',
            'stock' => 4,
        ]);
        $this->product($sepeda, [
            'slug' => 'tenda-dome',
            'name' => 'Tenda Milik Sepeda',
            'stock' => 2,
        ]);

        $this->get(route('booking.gokemping.create', ['product' => 'tenda-dome']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('booking/form')
                ->where('product.name', 'Tenda Milik GoKemping')
                ->where('product.stock', 4)
            );

        $this->get(route('booking.sewaSepedaGarut.create', ['product' => 'tenda-dome']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('booking/form')
                ->where('product.name', 'Tenda Milik Sepeda')
                ->where('product.stock', 2)
            );
    }

    public function test_produk_unit_lain_tidak_bisa_dibooking_lewat_path_unit_ini(): void
    {
        $gokemping = $this->business('gokemping');
        $sepeda = $this->business('sewa-sepeda-garut');

        $this->product($gokemping);
        $foreign = $this->product($sepeda, ['slug' => 'sepeda-mtb']);

        $this->get(route('booking.gokemping.create', ['product' => $foreign->slug]))
            ->assertNotFound();
    }

    public function test_produk_nonaktif_menghasilkan_404(): void
    {
        $business = $this->business('gokemping');
        $product = $this->product($business);
        $product->update(['is_active' => false]);

        $this->get(route('booking.gokemping.create', ['product' => $product->slug]))
            ->assertNotFound();
    }

    public function test_produk_terhapus_menghasilkan_404(): void
    {
        $business = $this->business('gokemping');
        $product = $this->product($business);
        $product->delete();

        $this->get(route('booking.gokemping.create', ['product' => $product->slug]))
            ->assertNotFound();
    }

    public function test_slug_tak_ada_menghasilkan_404(): void
    {
        $this->business('gokemping');

        $this->get(route('booking.gokemping.create', ['product' => 'tidak-ada']))
            ->assertNotFound();
    }

    public function test_unit_nonaktif_menghasilkan_404(): void
    {
        $business = $this->business('gokemping', isActive: false);
        $product = $this->product($business);

        $this->get(route('booking.gokemping.create', ['product' => $product->slug]))
            ->assertNotFound();
    }

    public function test_admin_yang_login_tetap_bisa_membuka_form_booking_unit_lain(): void
    {
        $own = $this->business('gokemping');
        $other = $this->business('sewa-sepeda-garut');

        $this->product($own);
        $foreign = $this->product($other, ['name' => 'Sepeda MTB']);

        $this->actingAs(User::factory()->forBusiness($own)->create())
            ->get(route('booking.sewaSepedaGarut.create', ['product' => $foreign->slug]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('booking/form')
                ->where('product.name', 'Sepeda MTB')
            );
    }

    public function test_halaman_form_booking_tidak_menyimpan_booking(): void
    {
        $business = $this->business('gokemping');
        $product = $this->product($business);

        $this->get(route('booking.gokemping.create', ['product' => $product->slug]))
            ->assertOk();

        $this->assertSame(0, Booking::count());
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
