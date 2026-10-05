<?php

namespace Tests\Feature\Admin;

use App\Models\Booking;
use App\Models\Business;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Support\SessionKey;
use Tests\TestCase;

/**
 * Manajemen produk dari sisi admin (PRD section 23, ROADMAP 4.3).
 *
 * Setiap test memakai admin dengan business-nya sendiri supaya pengujian isolasi
 * tenant tidak bergantung pada state dari test lain.
 */
class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get(route('admin.products.index'))
            ->assertRedirect(route('login'));
    }

    public function test_guests_cannot_store_a_product(): void
    {
        $this->post(route('admin.products.store'), [
            'name' => 'Tenda Dome',
        ])->assertRedirect(route('login'));

        $this->assertDatabaseCount('products', 0);
    }

    public function test_admin_sees_their_own_products(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->forBusiness($user->business)->create(['name' => 'Camping']);

        Product::factory()
            ->forBusiness($user->business)
            ->withCategory($category)
            ->create([
                'name' => 'Tenda Dome 4 Person',
                'slug' => 'tenda-dome-4-person',
                'price' => 75000,
                'stock' => 3,
            ]);

        // Produk unit lain tidak boleh bocor ke daftar admin ini.
        Product::factory()
            ->forBusiness(Business::factory()->create())
            ->create(['name' => 'Tenda Milik Unit Lain']);

        $this->actingAs($user)
            ->get(route('admin.products.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/products/index')
                ->has('products.data', 1)
                ->where('products.data.0.name', 'Tenda Dome 4 Person')
                ->where('products.data.0.slug', 'tenda-dome-4-person')
                ->where('products.data.0.category', 'Camping')
                ->where('products.data.0.price', 75000)
                ->where('products.data.0.price_label', 'Rp75.000')
                ->where('products.data.0.price_unit', 'hari')
                ->where('products.data.0.stock', 3)
                ->where('products.data.0.is_active', true)
                ->where('products.data.0.is_available', true)
                ->where('products.data.0.images_count', 0)
                ->where('products.data.0.photo', null)
            );
    }

    public function test_inactive_products_stay_visible_for_admin(): void
    {
        $user = User::factory()->create();

        Product::factory()->forBusiness($user->business)->inactive()->create();

        $this->actingAs($user)
            ->get(route('admin.products.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('products.data', 1)
                ->where('products.data.0.is_active', false)
            );
    }

    public function test_soft_deleted_products_are_hidden_from_the_list(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->forBusiness($user->business)->create();
        $product->delete();

        $this->actingAs($user)
            ->get(route('admin.products.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('products.data', 0));
    }

    public function test_products_without_stock_are_reported_as_unavailable(): void
    {
        $user = User::factory()->create();

        Product::factory()->forBusiness($user->business)->create(['stock' => 0]);

        $this->actingAs($user)
            ->get(route('admin.products.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('products.data', 1)
                ->where('products.data.0.stock', 0)
                ->where('products.data.0.is_available', false)
            );
    }

    public function test_admin_can_search_products_by_name_and_description(): void
    {
        $user = User::factory()->create();

        Product::factory()->forBusiness($user->business)->create([
            'name' => 'Tenda Dome 4 Person',
            'description' => 'Bahan waterproof, Termasuk tapak tenda.',
        ]);

        Product::factory()->forBusiness($user->business)->create([
            'name' => 'Kursi Lipat',
            'description' => 'Untuk piknik.',
        ]);

        $this->actingAs($user)
            ->get(route('admin.products.index', ['q' => 'tenda']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('products.data', 1)
                ->where('products.data.0.name', 'Tenda Dome 4 Person')
                ->where('filters.q', 'tenda')
            );

        // Kata yang hanya ada di deskripsi juga harus ditemukan.
        $this->actingAs($user)
            ->get(route('admin.products.index', ['q' => 'waterproof']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('products.data', 1)
                ->where('products.data.0.name', 'Tenda Dome 4 Person')
            );
    }

    public function test_admin_can_filter_products_by_category(): void
    {
        $user = User::factory()->create();
        $camping = Category::factory()->forBusiness($user->business)->create(['name' => 'Camping']);
        $sepeda = Category::factory()->forBusiness($user->business)->create(['name' => 'Sepeda']);

        Product::factory()->forBusiness($user->business)->withCategory($camping)->create();
        Product::factory()->forBusiness($user->business)->withCategory($sepeda)->create();

        $this->actingAs($user)
            ->get(route('admin.products.index', ['category' => $camping->getKey()]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('products.data', 1)
                ->where('filters.category', $camping->getKey())
            );
    }

    public function test_category_filter_cannot_reach_another_business_category(): void
    {
        $user = User::factory()->create();
        $otherCategory = Category::factory()->forBusiness(Business::factory()->create())->create();

        Product::factory()->forBusiness($user->business)->create();

        $this->actingAs($user)
            ->get(route('admin.products.index', ['category' => $otherCategory->getKey()]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('products.data', 0));
    }

    public function test_admin_can_filter_products_by_status(): void
    {
        $user = User::factory()->create();

        Product::factory()->forBusiness($user->business)->create();
        Product::factory()->forBusiness($user->business)->inactive()->create();

        $this->actingAs($user)
            ->get(route('admin.products.index', ['status' => 'nonaktif']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('products.data', 1)
                ->where('products.data.0.is_active', false)
                ->where('filters.status', 'nonaktif')
            );

        $this->actingAs($user)
            ->get(route('admin.products.index', ['status' => 'aktif']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('products.data', 1)
                ->where('products.data.0.is_active', true)
            );
    }

    public function test_invalid_filter_values_are_ignored_instead_of_rejected(): void
    {
        $user = User::factory()->create();

        Product::factory()->forBusiness($user->business)->create();

        $this->actingAs($user)
            ->get(route('admin.products.index', [
                'status' => 'nanti',
                'category' => '-5',
            ]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('products.data', 1)
                ->where('filters.status', 'semua')
                ->where('filters.category', null)
            );
    }

    public function test_admin_can_open_the_create_page(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->forBusiness($user->business)->create();

        $this->actingAs($user)
            ->get(route('admin.products.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/products/create')
                ->has('categories', 1)
                ->where('categories.0.name', $category->name)
                ->where('priceUnits', ['hari', 'jam', 'paket', 'event'])
                ->where('maxImages', 8)
            );
    }

    public function test_admin_can_store_a_product(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->forBusiness($user->business)->create();

        $this->actingAs($user)
            ->post(route('admin.products.store'), [
                'category_id' => $category->getKey(),
                'name' => '  Tenda Dome 4 Person  ',
                'description' => '  Sudah termasuk tapak tenda.  ',
                'specification' => [
                    ['key' => 'Kapasitas', 'value' => '4 orang'],
                    ['key' => ' Berat ', 'value' => ' 12 kg '],
                    ['key' => '  ', 'value' => '  '],
                ],
                'rental_terms' => 'Wajib DP 50%.',
                'price' => 75000,
                'price_unit' => 'hari',
                'stock' => 4,
            ])
            ->assertRedirect();

        $product = Product::query()
            ->forBusiness($user->business)
            ->where('name', 'Tenda Dome 4 Person')
            ->firstOrFail();

        $this->assertSame('tenda-dome-4-person', $product->slug);
        $this->assertSame('Sudah termasuk tapak tenda.', $product->description);
        $this->assertSame('Wajib DP 50%.', $product->rental_terms);
        $this->assertSame(75000, $product->price);
        $this->assertSame('hari', $product->price_unit);
        $this->assertSame(4, $product->stock);
        $this->assertTrue($product->is_active);
        $this->assertSame($category->getKey(), $product->category_id);
        // Baris spesifikasi yang kosong tidak ikut disimpan, dan label ikut
        // dirapikan supaya tampilan di halaman detail rapi. Urutan label tidak
        // diuji di sini: kolom JSON di MySQL menormalkan urutan kuncinya.
        $this->assertEqualsCanonicalizing(
            ['Kapasitas' => '4 orang', 'Berat' => '12 kg'],
            $product->specification,
        );
    }

    public function test_stored_product_redirects_to_the_edit_page(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->forBusiness($user->business)->create();

        // Foto disimpan setelah produknya ada, jadi admin langsung diarahkan ke
        // halaman edit tempat galeri foto dikelola. Slug diprediksi supaya
        // tujuan redirect ikut terverifikasi.
        $this->actingAs($user)
            ->post(route('admin.products.store'), [
                'category_id' => $category->getKey(),
                'name' => 'Tenda Dome',
                'price' => 75000,
                'price_unit' => 'hari',
                'stock' => 1,
            ])
            ->assertRedirect(route('admin.products.edit', 'tenda-dome'));
    }

    public function test_store_flashes_a_success_toast(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->forBusiness($user->business)->create();

        $response = $this->actingAs($user)->post(route('admin.products.store'), [
            'category_id' => $category->getKey(),
            'name' => 'Tenda Dome',
            'price' => 75000,
            'price_unit' => 'hari',
            'stock' => 1,
        ]);

        $response->assertInertiaFlash('toast');

        /** @var array{type: string, message: string} $toast */
        $toast = session(SessionKey::FLASH_DATA.'.toast');

        $this->assertSame('success', $toast['type']);
        $this->assertStringContainsString('Tenda Dome', $toast['message']);
    }

    public function test_duplicate_product_name_gets_a_numbered_slug(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->forBusiness($user->business)->create();

        Product::factory()
            ->forBusiness($user->business)
            ->create(['name' => 'Tenda Dome', 'slug' => 'tenda-dome']);

        $this->actingAs($user)
            ->post(route('admin.products.store'), [
                'category_id' => $category->getKey(),
                'name' => 'Tenda Dome',
                'price' => 75000,
                'price_unit' => 'hari',
                'stock' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('products', [
            'business_id' => $user->business_id,
            'slug' => 'tenda-dome-2',
        ]);
    }

    public function test_product_name_using_a_reserved_public_path_gets_suffixed(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->forBusiness($user->business)->create();

        $this->actingAs($user)
            ->post(route('admin.products.store'), [
                'category_id' => $category->getKey(),
                'name' => 'Success',
                'price' => 75000,
                'price_unit' => 'hari',
                'stock' => 1,
            ])
            ->assertRedirect();

        // `success` dipakai route halaman booking. Nama produknya tetap boleh
        // dipakai, hanya URL-nya yang dikecilkan supaya tidak menabrak route.
        $this->assertDatabaseHas('products', [
            'business_id' => $user->business_id,
            'name' => 'Success',
            'slug' => 'success-2',
        ]);
    }

    public function test_product_name_without_usable_slug_falls_back_to_readable_slug(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->forBusiness($user->business)->create();

        Product::factory()
            ->forBusiness($user->business)
            ->create(['name' => 'Produk', 'slug' => 'produk']);

        $this->actingAs($user)
            ->post(route('admin.products.store'), [
                'category_id' => $category->getKey(),
                'name' => '🔥🔥🔥',
                'price' => 75000,
                'price_unit' => 'hari',
                'stock' => 1,
            ])
            ->assertRedirect();

        // Nama tanpa huruf latin memakai `produk` sebagai dasarnya, dan
        // penomoran ikut memakai basis itu, bukan string kosong. URL seperti
        // `-2` tidak pernah muncul karena tidak bisa diklik dengan benar.
        $this->assertDatabaseHas('products', [
            'business_id' => $user->business_id,
            'slug' => 'produk-2',
        ]);
    }

    public function test_store_requires_a_category(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.products.store'), [
                'name' => 'Tenda Dome',
                'price' => 75000,
                'price_unit' => 'hari',
                'stock' => 1,
            ])
            ->assertSessionHasErrors('category_id');

        $this->assertDatabaseCount('products', 0);
    }

    public function test_store_rejects_a_category_from_another_business(): void
    {
        $user = User::factory()->create();
        $otherCategory = Category::factory()->forBusiness(Business::factory()->create())->create();

        $this->actingAs($user)
            ->post(route('admin.products.store'), [
                'category_id' => $otherCategory->getKey(),
                'name' => 'Tenda Dome',
                'price' => 75000,
                'price_unit' => 'hari',
                'stock' => 1,
            ])
            ->assertSessionHasErrors('category_id');

        $this->assertDatabaseCount('products', 0);
    }

    public function test_store_requires_name_price_unit_and_stock(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->forBusiness($user->business)->create();

        $this->actingAs($user)
            ->post(route('admin.products.store'), [
                'category_id' => $category->getKey(),
                'name' => '   ',
                'price' => null,
                'price_unit' => 'harii',
                'stock' => -1,
            ])
            ->assertSessionHasErrors(['name', 'price', 'price_unit', 'stock']);

        $this->assertDatabaseCount('products', 0);
    }

    public function test_store_rejects_negative_price(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->forBusiness($user->business)->create();

        $this->actingAs($user)
            ->post(route('admin.products.store'), [
                'category_id' => $category->getKey(),
                'name' => 'Tenda Dome',
                'price' => -1000,
                'price_unit' => 'hari',
                'stock' => 1,
            ])
            ->assertSessionHasErrors('price');
    }

    public function test_store_rejects_a_half_filled_specification_row(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->forBusiness($user->business)->create();

        $this->actingAs($user)
            ->post(route('admin.products.store'), [
                'category_id' => $category->getKey(),
                'name' => 'Tenda Dome',
                'price' => 75000,
                'price_unit' => 'hari',
                'stock' => 1,
                'specification' => [
                    ['key' => 'Kapasitas', 'value' => ''],
                ],
            ])
            ->assertSessionHasErrors('specification.0.value');

        $this->assertDatabaseCount('products', 0);
    }

    public function test_store_rejects_duplicate_specification_labels(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->forBusiness($user->business)->create();

        // Dua baris dengan label sama akan saling menimpa di kolom JSON, jadi
        // admin harus diberi tahu, bukan diam-diam kehilangan salah satunya.
        $this->actingAs($user)
            ->post(route('admin.products.store'), [
                'category_id' => $category->getKey(),
                'name' => 'Tenda Dome',
                'price' => 75000,
                'price_unit' => 'hari',
                'stock' => 1,
                'specification' => [
                    ['key' => 'Kapasitas', 'value' => '4 orang'],
                    ['key' => 'Kapasitas', 'value' => '6 orang'],
                ],
            ])
            ->assertSessionHasErrors('specification');

        $this->assertDatabaseCount('products', 0);
    }

    public function test_admin_can_open_the_edit_page(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->forBusiness($user->business)->create();
        $product = Product::factory()
            ->forBusiness($user->business)
            ->withCategory($category)
            ->create([
                'name' => 'Tenda Dome',
                'slug' => 'tenda-dome',
                'specification' => ['Kapasitas' => '4 orang', 'Berat' => '12 kg'],
            ]);

        $this->actingAs($user)
            ->get(route('admin.products.edit', $product))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/products/edit')
                ->where('product.name', 'Tenda Dome')
                ->where('product.slug', 'tenda-dome')
                ->where('product.category_id', $category->getKey())
                // Spesifikasi dikirim sebagai baris label-isi. Urutannya ikut
                // urutan key di kolom JSON, yang dinormalkan MySQL, jadi test ini
                // memakai urutan yang benar-benar tersimpan.
                ->where('product.specification', [
                    ['key' => 'Berat', 'value' => '12 kg'],
                    ['key' => 'Kapasitas', 'value' => '4 orang'],
                ])
                ->where('product.bookingCount', 0)
                ->where('maxImages', 8)
                ->where('remainingImages', 8)
                ->where('priceUnits', ['hari', 'jam', 'paket', 'event'])
            );
    }

    public function test_admin_cannot_open_the_edit_page_of_another_business(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->forBusiness(Business::factory()->create())->create();

        $this->actingAs($user)
            ->get(route('admin.products.edit', $product))
            ->assertNotFound();
    }

    public function test_admin_can_update_a_product(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->forBusiness($user->business)->create();
        $otherCategory = Category::factory()->forBusiness($user->business)->create();
        $product = Product::factory()
            ->forBusiness($user->business)
            ->create([
                'name' => 'Tenda Dome',
                'slug' => 'tenda-dome',
                'price' => 75000,
                'stock' => 2,
            ]);

        $this->actingAs($user)
            ->patch(route('admin.products.update', $product), [
                'category_id' => $otherCategory->getKey(),
                'name' => 'Tenda Dome 4 Person',
                'description' => 'Sudah termasuk tapak tenda.',
                'specification' => [
                    ['key' => 'Kapasitas', 'value' => '4 orang'],
                ],
                'rental_terms' => '',
                'price' => 85000,
                'price_unit' => 'paket',
                'stock' => 5,
            ])
            ->assertRedirect(route('admin.products.edit', $product))
            ->assertSessionHasNoErrors();

        $product->refresh();

        $this->assertSame('Tenda Dome 4 Person', $product->name);
        // Slug lama dipertahankan: URL katalog dan halaman booking yang sudah
        // dibagikan tidak boleh ikut putus gara-gara nama diedit.
        $this->assertSame('tenda-dome', $product->slug);
        $this->assertSame($otherCategory->getKey(), $product->category_id);
        $this->assertSame(['Kapasitas' => '4 orang'], $product->specification);
        $this->assertNull($product->rental_terms);
        $this->assertSame(85000, $product->price);
        $this->assertSame('paket', $product->price_unit);
        $this->assertSame(5, $product->stock);
    }

    public function test_editing_a_product_may_clear_its_category(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->forBusiness($user->business)->create();
        $product = Product::factory()
            ->forBusiness($user->business)
            ->withCategory($category)
            ->create();

        $this->actingAs($user)
            ->patch(route('admin.products.update', $product), [
                'category_id' => '',
                'name' => $product->name,
                'price' => 75000,
                'price_unit' => 'hari',
                'stock' => 1,
            ])
            ->assertSessionHasNoErrors();

        $product->refresh();

        // Kategori kosong harus disimpan sebagai null, bukan id 0 yang tidak
        // ada di tabel kategori.
        $this->assertNull($product->category_id);
    }

    public function test_editing_a_product_keeps_its_active_status_when_the_form_omits_the_field(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->forBusiness($user->business)->create();
        $product = Product::factory()
            ->forBusiness($user->business)
            ->withCategory($category)
            ->create();

        $this->actingAs($user)
            ->patch(route('admin.products.update', $product), [
                'category_id' => $category->getKey(),
                'name' => 'Tenda Dome Baru',
                'price' => 75000,
                'price_unit' => 'hari',
                'stock' => 1,
            ])
            ->assertRedirect();

        $this->assertTrue($product->refresh()->is_active);
    }

    public function test_editing_a_product_deactivates_it_when_the_checkbox_is_unchecked(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->forBusiness($user->business)->create();
        $product = Product::factory()
            ->forBusiness($user->business)
            ->withCategory($category)
            ->create();

        // Checkbox HTML yang tidak dicentang tidak mengirim apa pun. Form edit
        // mengirim hidden `is_active=0` supaya status tetap bisa dimatikan dari
        // checkbox, jadi nilai `0` di sini yang diuji, bukan field yang dihilang.
        $this->actingAs($user)
            ->patch(route('admin.products.update', $product), [
                'category_id' => $category->getKey(),
                'name' => $product->name,
                'price' => 75000,
                'price_unit' => 'hari',
                'stock' => 1,
                'is_active' => '0',
            ])
            ->assertRedirect();

        $this->assertFalse($product->refresh()->is_active);
    }

    public function test_editing_a_product_does_not_accept_photo_uploads(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $category = Category::factory()->forBusiness($user->business)->create();
        $product = Product::factory()
            ->forBusiness($user->business)
            ->withCategory($category)
            ->create();

        // Form edit memisahkan unggah foto ke form galerinya sendiri. Kalau aturan
        // foto ikut di request update, request ini akan menerima berkas lalu
        // membuangnya tanpa penjelasan.
        $this->actingAs($user)
            ->patch(route('admin.products.update', $product), [
                'category_id' => $category->getKey(),
                'name' => $product->name,
                'price' => 75000,
                'price_unit' => 'hari',
                'stock' => 1,
                'images' => [UploadedFile::fake()->image('foto.jpg')],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(0, $product->images()->count());
        $this->assertSame([], Storage::disk('public')->allFiles('products/'.$product->getKey()));
    }

    public function test_admin_can_deactivate_a_product_from_the_edit_form(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->forBusiness($user->business)->create();
        $product = Product::factory()
            ->forBusiness($user->business)
            ->withCategory($category)
            ->create();

        $this->actingAs($user)
            ->patch(route('admin.products.update', $product), [
                'category_id' => $category->getKey(),
                'name' => $product->name,
                'price' => 75000,
                'price_unit' => 'hari',
                'stock' => 1,
                'is_active' => '0',
            ])
            ->assertRedirect();

        $this->assertFalse($product->refresh()->is_active);
    }

    public function test_admin_cannot_update_a_product_of_another_business(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->forBusiness(Business::factory()->create())->create([
            'name' => 'Tenda Asli',
        ]);

        $this->actingAs($user)
            ->patch(route('admin.products.update', $product), [
                'name' => 'Diretas',
            ])
            ->assertNotFound();

        $this->assertSame('Tenda Asli', $product->refresh()->name);
    }

    public function test_admin_can_toggle_product_status(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->forBusiness($user->business)->create();

        $this->actingAs($user)
            ->from(route('admin.products.index'))
            ->patch(route('admin.products.status', $product), ['is_active' => '0'])
            ->assertRedirect(route('admin.products.index'));

        $this->assertFalse($product->refresh()->is_active);

        $this->actingAs($user)
            ->patch(route('admin.products.status', $product), ['is_active' => '1'])
            ->assertRedirect(route('admin.products.index'));

        $this->assertTrue($product->refresh()->is_active);
    }

    public function test_status_requires_a_boolean_value(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->forBusiness($user->business)->create();

        $this->actingAs($user)
            ->patch(route('admin.products.status', $product), ['is_active' => 'nanti'])
            ->assertSessionHasErrors('is_active');

        $this->assertTrue($product->refresh()->is_active);
    }

    public function test_admin_cannot_toggle_status_of_another_business_product(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->forBusiness(Business::factory()->create())->create();

        $this->actingAs($user)
            ->patch(route('admin.products.status', $product), ['is_active' => '0'])
            ->assertNotFound();

        $this->assertTrue($product->refresh()->is_active);
    }

    public function test_admin_can_soft_delete_a_product(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->forBusiness($user->business)->create(['name' => 'Tenda Dome']);

        $this->actingAs($user)
            ->from(route('admin.products.index'))
            ->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('admin.products.index'));

        $this->assertSoftDeleted('products', ['id' => $product->getKey()]);
        // Barisnya tetap ada supaya produknya masih bisa dipulihkan.
        $this->assertNotNull(Product::withTrashed()->find($product->getKey()));
        // Produk dihapus berarti tidak boleh lagi bisa disewa.
        $this->assertFalse($product->refresh()->is_active);
    }

    public function test_soft_deleted_product_keeps_its_images_and_booking_history(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->forBusiness($user->business)->create();
        $image = ProductImage::factory()->for($product)->create();
        $booking = Booking::factory()->forBusiness($user->business)->create();

        $booking->items()->create([
            'product_id' => $product->getKey(),
            'product_name' => $product->name,
            'price_unit' => $product->price_unit,
            'quantity' => 1,
            'total_days' => $booking->total_days,
            'price' => $product->price,
            'subtotal' => $product->price * $booking->total_days,
        ]);

        $this->actingAs($user)
            ->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('admin.products.index'));

        // Foto dan detail booking tidak ikut hilang. Data booking sudah menyalin
        // nama dan harga produk saat transaksi dibuat, jadi produk yang dihapus
        // tidak merusak riwayat yang sudah lewat.
        $this->assertDatabaseHas('product_images', ['id' => $image->getKey()]);
        $this->assertDatabaseHas('booking_items', [
            'booking_id' => $booking->getKey(),
            'product_id' => $product->getKey(),
            'product_name' => $product->name,
        ]);
    }

    public function test_delete_toast_mentions_booking_history(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->forBusiness($user->business)->create(['name' => 'Tenda Dome']);
        $booking = Booking::factory()->forBusiness($user->business)->create();

        $booking->items()->create([
            'product_id' => $product->getKey(),
            'product_name' => $product->name,
            'price_unit' => $product->price_unit,
            'quantity' => 2,
            'total_days' => $booking->total_days,
            'price' => $product->price,
            'subtotal' => $product->price * 2 * $booking->total_days,
        ]);

        $response = $this->actingAs($user)
            ->delete(route('admin.products.destroy', $product));

        $response->assertInertiaFlash('toast');

        /** @var array{type: string, message: string} $toast */
        $toast = session(SessionKey::FLASH_DATA.'.toast');

        $this->assertSame('success', $toast['type']);
        $this->assertStringContainsString('Tenda Dome', $toast['message']);
        $this->assertStringContainsString('1 booking', $toast['message']);
    }

    public function test_admin_cannot_delete_a_product_of_another_business(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->forBusiness(Business::factory()->create())->create();

        $this->actingAs($user)
            ->delete(route('admin.products.destroy', $product))
            ->assertNotFound();

        $this->assertDatabaseHas('products', [
            'id' => $product->getKey(),
            'deleted_at' => null,
        ]);
    }
}
