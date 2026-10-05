<?php

namespace App\Http\Requests;

use App\Models\Product;

/**
 * Validasi perubahan produk (ROADMAP 4.3).
 *
 * Aturannya sama dengan `StoreProductRequest`, dengan dua perbedaan yang
 * disengaja:
 *
 * - Slug tidak ikut di payload. Slug ada di URL katalog dan halaman booking, jadi
 *   produk yang slug-nya berubah akan mengganti tautan yang sudah dibagikan.
 *   Nama produk tetap boleh diubah, slugnya tidak.
 * - Kategori boleh dikosongkan. `products.category_id` nullable, jadi form edit
 *   masih bisa mengembalikan produk ke kondisi tanpa kategori tanpa harus
 *   menghapus produknya.
 *
 * `is_active` selalu ikut di payload dari form edit, tapi tetap dijaga supaya
 * request yang sengaja tidak mengirimnya (misalnya form lain di masa depan) tidak
 * diam-diam menonaktifkan produk yang sedang aktif.
 */
class UpdateProductRequest extends StoreProductRequest
{
    /**
     * Aturan produk saja, tanpa aturan foto.
     *
     * Form edit memisahkan unggah foto ke form-nya sendiri, jadi foto tidak ikut
     * tersimpan ulang bersama produk. Kalau aturan foto ikut di sini, request edit
     * akan menerima berkas foto lalu membuangnya tanpa penjelasan.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->productRules(categoryRequired: false);
    }

    /**
     * Atribut siap disimpan. Slug lama dipertahankan.
     *
     * @return array<string, mixed>
     */
    public function productPayload(): array
    {
        /** @var Product $product */
        $product = $this->route('product');

        return [
            ...parent::productPayload(),
            'slug' => $product->slug,
            'is_active' => $this->has('is_active') ? $this->boolean('is_active') : (bool) $product->is_active,
        ];
    }
}
