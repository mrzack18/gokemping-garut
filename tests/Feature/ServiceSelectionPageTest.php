<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ServiceSelectionPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_pilih_layanan_dapat_diakses_tanpa_login(): void
    {
        $this->get(route('services.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('services/index')
                ->has('businesses')
                ->has('previewProducts')
            );
    }

    public function test_hanya_unit_aktif_yang_ditampilkan(): void
    {
        Business::factory()->create(['name' => 'GoKemping', 'is_active' => true]);
        Business::factory()->create(['name' => 'Unit Mati', 'is_active' => false]);

        $this->get(route('services.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('services/index')
                ->has('businesses', 1)
                ->where('businesses.0.name', 'GoKemping')
            );
    }

    public function test_kirim_prefix_kode_booking(): void
    {
        $business = Business::factory()->create(['booking_code_prefix' => 'GK']);

        $this->get(route('services.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('services/index')
                ->where('businesses.0.booking_code_prefix', 'GK')
            );

        $this->assertNotNull($business->booking_code_prefix);
    }

    public function test_contoh_produk_hanya_berasal_dari_unit_yang_sesuai(): void
    {
        $first = Business::factory()->create();
        $second = Business::factory()->create();

        Product::factory()->forBusiness($first)->create(['name' => 'Tenda Dome']);
        Product::factory()->forBusiness($second)->create(['name' => 'Sepeda MTB']);

        $this->get(route('services.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('services/index')
                ->has('previewProducts', 2)
                ->where('previewProducts.0.business_id', $first->id)
                ->where('previewProducts.0.products.0.name', 'Tenda Dome')
                ->where('previewProducts.1.business_id', $second->id)
                ->where('previewProducts.1.products.0.name', 'Sepeda MTB')
            );
    }

    public function test_produk_nonaktif_dan_stok_nol_tidak_dimasukkan(): void
    {
        $business = Business::factory()->create();

        Product::factory()->forBusiness($business)->create(['name' => 'Produk Aktif']);
        Product::factory()->forBusiness($business)->inactive()->create(['name' => 'Produk Nonaktif']);
        Product::factory()->forBusiness($business)->create([
            'name' => 'Produk Habis',
            'stock' => 0,
        ]);

        $this->get(route('services.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('services/index')
                ->has('previewProducts.0.products', 1)
                ->where('previewProducts.0.products.0.name', 'Produk Aktif')
            );
    }

    public function test_contoh_produk_dibatasi_tiga_item(): void
    {
        $business = Business::factory()->create();

        Product::factory()->count(6)->forBusiness($business)->create();

        $this->get(route('services.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('services/index')
                ->has('previewProducts.0.products', 3)
            );
    }

    public function test_admin_yang_login_tetap_melihat_semua_unit(): void
    {
        $own = Business::factory()->create(['name' => 'Unit Sendiri']);
        $other = Business::factory()->create(['name' => 'Unit Lain']);

        Product::factory()->forBusiness($own)->create();
        Product::factory()->forBusiness($other)->create();

        $this->actingAs(User::factory()->forBusiness($own)->create())
            ->get(route('services.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('services/index')
                ->has('businesses', 2)
                ->has('previewProducts', 2)
            );
    }
}
