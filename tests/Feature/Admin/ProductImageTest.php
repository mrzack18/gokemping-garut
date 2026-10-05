<?php

namespace Tests\Feature\Admin;

use App\Models\Business;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use App\Services\ProductImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Support\SessionKey;
use RuntimeException;
use Tests\TestCase;

/**
 * Foto produk dari sisi admin (PRD section 23 & 33, ROADMAP 4.3).
 *
 * `product_images` tidak punya kolom `business_id`, jadi setiap test juga memeriksa
 * bahwa foto milik produk unit lain tidak bisa dijangkau dari halaman admin unit
 * ini. Ini yang membuat foto tidak bisa ikut bocor saat admin menebak angka id.
 *
 * Disk publik dipalsukan dengan `Storage::fake()`, jadi test tidak menulis berkas
 * sungguhan ke disk.
 */
class ProductImageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_guests_cannot_upload_product_images(): void
    {
        $product = Product::factory()->forBusiness(Business::factory()->create())->create();

        $this->post(route('admin.products.images.store', $product), [
            'images' => [UploadedFile::fake()->image('tenda.jpg')],
        ])->assertRedirect(route('login'));

        $this->assertDatabaseCount('product_images', 0);
    }

    public function test_admin_can_upload_a_product_image(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->forBusiness($user->business)->create();

        $this->actingAs($user)
            ->post(route('admin.products.images.store', $product), [
                'images' => [UploadedFile::fake()->image('tenda.jpg', 2000, 2000)],
            ])
            ->assertRedirect(route('admin.products.edit', $product))
            ->assertSessionHasNoErrors();

        $image = $product->images()->firstOrFail();

        // Foto pertama otomatis menjadi foto utama produk.
        $this->assertTrue($image->is_primary);
        $this->assertSame(1, $image->sort_order);
        // Berkas disimpan sebagai WebP di folder produk, bukan dengan nama asli
        // dari perangkat admin.
        $this->assertStringStartsWith('products/'.$product->getKey().'/', $image->image);
        $this->assertStringEndsWith('.webp', $image->image);
        Storage::disk('public')->assertExists($image->image);
    }

    public function test_uploaded_image_is_resized_and_converted_to_webp(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->forBusiness($user->business)->create();

        $this->actingAs($user)
            ->post(route('admin.products.images.store', $product), [
                'images' => [UploadedFile::fake()->image('besar.jpg', 3000, 2000)],
            ]);

        $image = $product->images()->firstOrFail();
        $contents = Storage::disk('public')->get($image->image);

        $this->assertNotFalse(
            getimagesizefromstring($contents),
            'Berkas hasil unggahan harus tetap gambar yang bisa dibaca.',
        );
        $this->assertSame('image/webp', getimagesizefromstring($contents)['mime']);
        // Sisi terpanjang dipotong 1200 piksel supaya ukuran berkasnya tidak
        // ikut jadi besar hanya karena foto diambil dengan kamera bagus.
        [$width, $height] = getimagesizefromstring($contents);
        $this->assertSame(1200, max($width, $height));
    }

    public function test_upload_flashes_a_success_toast(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->forBusiness($user->business)->create();

        $response = $this->actingAs($user)->post(route('admin.products.images.store', $product), [
            'images' => [
                UploadedFile::fake()->image('satu.jpg'),
                UploadedFile::fake()->image('dua.jpg'),
            ],
        ]);

        $response->assertInertiaFlash('toast');

        /** @var array{type: string, message: string} $toast */
        $toast = session(SessionKey::FLASH_DATA.'.toast');

        $this->assertSame('success', $toast['type']);
        $this->assertStringContainsString('2 foto', $toast['message']);
    }

    public function test_second_upload_does_not_replace_the_primary_image(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->forBusiness($user->business)->create();

        $this->actingAs($user)->post(route('admin.products.images.store', $product), [
            'images' => [UploadedFile::fake()->image('satu.jpg')],
        ]);

        $this->actingAs($user)->post(route('admin.products.images.store', $product), [
            'images' => [UploadedFile::fake()->image('dua.jpg')],
        ]);

        $images = $product->images()->orderBy('sort_order')->get();

        $this->assertCount(2, $images);
        $this->assertTrue($images[0]->is_primary);
        $this->assertFalse($images[1]->is_primary);
        // Foto baru ditambahkan di belakang, bukan disisipkan di tengah, supaya
        // urutan galeri yang sudah ditata admin tidak berubah sendiri.
        $this->assertSame([1, 2], $images->pluck('sort_order')->all());
    }

    public function test_upload_requires_at_least_one_image(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->forBusiness($user->business)->create();

        $this->actingAs($user)
            ->post(route('admin.products.images.store', $product))
            ->assertSessionHasErrors('images');

        $this->assertDatabaseCount('product_images', 0);
    }

    public function test_upload_rejects_non_image_files(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->forBusiness($user->business)->create();

        $this->actingAs($user)
            ->post(route('admin.products.images.store', $product), [
                'images' => [UploadedFile::fake()->create('dokumen.pdf', 10, 'application/pdf')],
            ])
            ->assertSessionHasErrors('images.0');

        $this->assertDatabaseCount('product_images', 0);
    }

    public function test_upload_rejects_files_above_the_size_limit(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->forBusiness($user->business)->create();

        $this->actingAs($user)
            ->post(route('admin.products.images.store', $product), [
                'images' => [UploadedFile::fake()->image('besar.jpg')->size(5121)],
            ])
            ->assertSessionHasErrors('images.0');

        $this->assertDatabaseCount('product_images', 0);
    }

    public function test_upload_stops_when_the_gallery_is_full(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->forBusiness($user->business)->create();

        for ($index = 0; $index < ProductImageService::MAX_IMAGES_PER_PRODUCT; $index++) {
            ProductImage::factory()->for($product)->create();
        }

        $this->actingAs($user)
            ->post(route('admin.products.images.store', $product), [
                'images' => [UploadedFile::fake()->image('tambahan.jpg')],
            ])
            ->assertSessionHasErrors('images');

        // Galeri yang sudah penuh tidak boleh bertambah, dan berkas yang baru
        // dipilih tidak boleh sempat tersimpan.
        $this->assertSame(
            ProductImageService::MAX_IMAGES_PER_PRODUCT,
            $product->images()->count(),
        );
        $this->assertEmpty(Storage::disk('public')->allFiles('products/'.$product->getKey()));
    }

    public function test_upload_rejects_more_files_than_the_remaining_slots(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->forBusiness($user->business)->create();

        ProductImage::factory()->count(6)->for($product)->create();

        $this->actingAs($user)
            ->post(route('admin.products.images.store', $product), [
                'images' => [
                    UploadedFile::fake()->image('satu.jpg'),
                    UploadedFile::fake()->image('dua.jpg'),
                    UploadedFile::fake()->image('tiga.jpg'),
                ],
            ])
            ->assertSessionHasErrors('images');

        // Sisa kapasitas hanya dua foto, jadi request tiga foto ditolak.
        $this->assertSame(6, $product->images()->count());
    }

    public function test_admin_cannot_upload_images_to_another_business_product(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->forBusiness(Business::factory()->create())->create();

        $this->actingAs($user)
            ->post(route('admin.products.images.store', $product), [
                'images' => [UploadedFile::fake()->image('tenda.jpg')],
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('product_images', 0);
    }

    public function test_photos_can_be_uploaded_together_with_a_new_product(): void
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
                'images' => [UploadedFile::fake()->image('tenda.jpg')],
            ])
            ->assertRedirect();

        $product = Product::query()->forBusiness($user->business)->firstOrFail();

        $this->assertSame(1, $product->images()->count());
        $this->assertTrue($product->images()->firstOrFail()->is_primary);
    }

    public function test_admin_can_make_an_image_the_primary_one(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->forBusiness($user->business)->create();
        $first = ProductImage::factory()->for($product)->primary()->create();
        $second = ProductImage::factory()->for($product)->create();

        $this->actingAs($user)
            ->patch(route('admin.products.images.update', [$product, $second]))
            ->assertRedirect(route('admin.products.edit', $product));

        // Hanya satu foto yang boleh jadi foto utama.
        $this->assertFalse($first->refresh()->is_primary);
        $this->assertTrue($second->refresh()->is_primary);
    }

    public function test_admin_cannot_change_the_primary_image_of_another_business_product(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->forBusiness(Business::factory()->create())->create();
        $first = ProductImage::factory()->for($product)->primary()->create();
        $second = ProductImage::factory()->for($product)->create();

        $this->actingAs($user)
            ->patch(route('admin.products.images.update', [$product, $second]))
            ->assertNotFound();

        $this->assertTrue($first->refresh()->is_primary);
        $this->assertFalse($second->refresh()->is_primary);
    }

    public function test_image_from_another_product_cannot_be_managed(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->forBusiness($user->business)->create();
        $otherProduct = Product::factory()->forBusiness($user->business)->create();
        $otherImage = ProductImage::factory()->for($otherProduct)->create();

        // URL-nya produk milik unit ini, tapi fotonya milik produk lain.
        $this->actingAs($user)
            ->patch(route('admin.products.images.update', [$product, $otherImage]))
            ->assertNotFound();

        $this->actingAs($user)
            ->delete(route('admin.products.images.destroy', [$product, $otherImage]))
            ->assertNotFound();

        $this->assertDatabaseHas('product_images', ['id' => $otherImage->getKey()]);
    }

    public function test_admin_can_delete_a_product_image(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->forBusiness($user->business)->create();
        $image = ProductImage::factory()->for($product)->create([
            'image' => 'products/'.$product->getKey().'/hapus.webp',
        ]);

        Storage::disk('public')->put($image->image, 'isi');

        $this->actingAs($user)
            ->delete(route('admin.products.images.destroy', [$product, $image]))
            ->assertRedirect(route('admin.products.edit', $product))
            ->assertInertiaFlash('toast');

        $this->assertDatabaseMissing('product_images', ['id' => $image->getKey()]);
        Storage::disk('public')->assertMissing($image->image);
    }

    public function test_deleting_the_primary_image_promotes_the_next_one(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->forBusiness($user->business)->create();
        $primary = ProductImage::factory()->for($product)->primary()->create(['sort_order' => 0]);
        $next = ProductImage::factory()->for($product)->create(['sort_order' => 1]);

        $this->actingAs($user)
            ->delete(route('admin.products.images.destroy', [$product, $primary]))
            ->assertRedirect(route('admin.products.edit', $product));

        // Produk tidak boleh tertinggal tanpa foto utama: katalog dan galeri
        // publik memakai foto utama sebagai gambar pertama.
        $this->assertTrue($next->refresh()->is_primary);
    }

    public function test_deleting_the_last_image_leaves_the_product_without_primary(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->forBusiness($user->business)->create();
        $primary = ProductImage::factory()->for($product)->primary()->create();

        $this->actingAs($user)
            ->delete(route('admin.products.images.destroy', [$product, $primary]))
            ->assertRedirect(route('admin.products.edit', $product));

        $this->assertSame(0, $product->images()->count());
    }

    public function test_admin_cannot_delete_an_image_of_another_business_product(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->forBusiness(Business::factory()->create())->create();
        $image = ProductImage::factory()->for($product)->create();

        $this->actingAs($user)
            ->delete(route('admin.products.images.destroy', [$product, $image]))
            ->assertNotFound();

        $this->assertDatabaseHas('product_images', ['id' => $image->getKey()]);
    }

    public function test_edit_page_reports_remaining_photo_slots(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->forBusiness($user->business)->create();

        ProductImage::factory()->count(3)->for($product)->create();

        $this->actingAs($user)
            ->get(route('admin.products.edit', $product))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('remainingImages', 5)
                ->has('product.photos', 3)
            );
    }

    /**
     * Foto yang gagal ditulis di tengah proses tidak boleh meninggalkan baris
     * database yang menunjuk berkas yang sudah dihapus.
     *
     * Simulasi: unggah dua foto, foto kedua bukan gambar yang bisa dibaca meski
     * lolos validasi. Baris foto pertama dan berkasnya harus dibatalkan
     * bersamaan. Kalau barisnya dibiarkan, katalog akan menampilkan foto yang
     * isinya sudah tidak ada.
     */
    public function test_failed_upload_rolls_back_earlier_rows_and_files(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->forBusiness($user->business)->create();

        $broken = UploadedFile::fake()->create('rusak.jpg', 8, 'image/jpeg');

        try {
            $this->actingAs($user)->post(route('admin.products.images.store', $product), [
                'images' => [
                    UploadedFile::fake()->image('bagus.jpg'),
                    $broken,
                ],
            ]);
        } catch (RuntimeException) {
            // Kegagalan pemrosesan gambar memang mengubahnya jadi error server.
            // Yang dicek di sini justru sisa-sisanya.
        }

        $this->assertSame(0, $product->images()->count());
        $this->assertEmpty(Storage::disk('public')->allFiles('products/'.$product->getKey()));
    }
}
