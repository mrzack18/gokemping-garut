<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_dapat_diakses_tanpa_login(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('welcome')
                ->has('businesses')
                ->has('featuredProducts')
            );
    }

    public function test_hanya_unit_business_aktif_yang_ditampilkan(): void
    {
        Business::factory()->create(['name' => 'GoKemping', 'is_active' => true]);
        Business::factory()->create(['name' => 'Unit Nonaktif', 'is_active' => false]);

        $this->get(route('home'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('welcome')
                ->has('businesses', 1)
                ->where('businesses.0.name', 'GoKemping')
            );
    }

    public function test_produk_unggulan_mencakup_semua_unit_business(): void
    {
        $camping = Business::factory()->create(['name' => 'GoKemping']);
        $sepeda = Business::factory()->create(['name' => 'Sewa Sepeda Garut']);

        Product::factory()->forBusiness($camping)->create([
            'name' => 'Tenda Dome 4 Person',
            'stock' => 5,
        ]);
        Product::factory()->forBusiness($sepeda)->create([
            'name' => 'Sepeda MTB Polygon Bromo',
            'stock' => 4,
        ]);

        $this->get(route('home'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('welcome')
                ->has('featuredProducts', 2)
                ->where('featuredProducts.0.business.name', 'GoKemping')
                ->where('featuredProducts.1.business.name', 'Sewa Sepeda Garut')
            );
    }

    public function test_produk_nonaktif_dan_stok_nol_tidak_ditampilkan(): void
    {
        $business = Business::factory()->create();

        Product::factory()->forBusiness($business)->create([
            'name' => 'Produk Aktif',
            'is_active' => true,
            'stock' => 3,
        ]);
        Product::factory()->forBusiness($business)->inactive()->create([
            'name' => 'Produk Nonaktif',
            'stock' => 3,
        ]);
        Product::factory()->forBusiness($business)->create([
            'name' => 'Produk Habis',
            'is_active' => true,
            'stock' => 0,
        ]);

        $this->get(route('home'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('welcome')
                ->has('featuredProducts', 1)
                ->where('featuredProducts.0.name', 'Produk Aktif')
            );
    }

    public function test_produk_dari_unit_nonaktif_tidak_ditampilkan(): void
    {
        $active = Business::factory()->create(['is_active' => true]);
        $inactive = Business::factory()->create(['is_active' => false]);

        Product::factory()->forBusiness($active)->create([
            'name' => 'Produk Aktif',
        ]);
        Product::factory()->forBusiness($inactive)->create([
            'name' => 'Produk Unit Mati',
        ]);

        $this->get(route('home'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('welcome')
                ->has('featuredProducts', 1)
                ->where('featuredProducts.0.name', 'Produk Aktif')
            );
    }

    public function test_admin_yang_login_tetap_melihat_katalog_lintas_unit(): void
    {
        $own = Business::factory()->create(['name' => 'Unit Sendiri']);
        $other = Business::factory()->create(['name' => 'Unit Lain']);

        Product::factory()->forBusiness($own)->create([
            'name' => 'Produk Sendiri',
        ]);
        Product::factory()->forBusiness($other)->create([
            'name' => 'Produk Unit Lain',
        ]);

        $admin = User::factory()->forBusiness($own)->create();

        $this->actingAs($admin)
            ->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('welcome')
                ->has('featuredProducts', 2)
            );
    }

    public function test_produk_unggulan_dibatasi_empat_item_per_unit(): void
    {
        $first = Business::factory()->create();
        $second = Business::factory()->create();

        Product::factory()->count(10)->forBusiness($first)->create();
        Product::factory()->count(10)->forBusiness($second)->create();

        $this->get(route('home'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('welcome')
                ->has('featuredProducts', 8)
                ->where('featuredProducts', fn ($products) => collect($products)
                    ->countBy(fn ($product) => $product['business']['id'])
                    ->all() === [$first->id => 4, $second->id => 4]
                )
            );
    }

    public function test_produk_unggulan_mengambil_produk_terbaru_per_unit(): void
    {
        $business = Business::factory()->create();

        $older = Product::factory()->forBusiness($business)->create([
            'name' => 'Produk Lama',
        ]);
        $newer = Product::factory()->forBusiness($business)->create([
            'name' => 'Produk Baru',
        ]);

        $this->get(route('home'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('welcome')
                ->has('featuredProducts', 2)
                ->where('featuredProducts.0.id', $newer->id)
                ->where('featuredProducts.1.id', $older->id)
            );
    }
}
