<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\ProductImageService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * Aksi foto produk dari panel galeri (ROADMAP 4.3).
 *
 * Foto diambil lewat relasi `$product->images()` yang sudah dibatasi
 * `BusinessScope`, bukan `ProductImage::find()`. `product_images` tidak punya
 * `business_id`, jadi pencarian global di tabel itu bisa menemukan foto milik
 * produk unit lain. Dengan begitu foto milik unit lain berakhir sebagai 404.
 */
class ProductImageController extends Controller
{
    /**
     * Jadikan satu foto sebagai foto utama produk.
     */
    public function update(Product $product, ProductImage $image, ProductImageService $photos): RedirectResponse
    {
        $this->ensureBelongsToProduct($product, $image);

        $photos->makePrimary($image);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Foto utama produk "'.$product->name.'" berhasil diubah.',
        ]);

        return to_route('admin.products.edit', $product);
    }

    /**
     * Hapus satu foto produk.
     *
     * Kalau foto yang dihapus sedang jadi foto utama, foto berikutnya otomatis
     * menjadi foto utama supaya produk tidak tertinggal tanpa foto utama.
     */
    public function destroy(Product $product, ProductImage $image, ProductImageService $photos): RedirectResponse
    {
        $this->ensureBelongsToProduct($product, $image);

        $photos->delete($image);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Foto berhasil dihapus dari produk "'.$product->name.'".',
        ]);

        return to_route('admin.products.edit', $product);
    }

    /**
     * Foto harus milik produk pada URL yang sedang dibuka.
     *
     * Route memang membawa dua parameter, jadi tanpa pemeriksaan ini admin bisa
     * menghapus foto produk lain hanya dengan menebak angka id-nya.
     */
    private function ensureBelongsToProduct(Product $product, ProductImage $image): void
    {
        abort_unless((int) $image->product_id === (int) $product->getKey(), 404);
    }
}
