<?php

namespace App\Http\Requests;

use App\Models\Product;
use App\Services\ProductImageService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Validasi unggah foto produk (ROADMAP 4.3, BR-08).
 *
 * Batas mime dan ukuran mengikuti BR-08 supaya aturan unggah konsisten dengan
 * bukti pembayaran. Bedanya, foto produk dikompresi ulang oleh
 * `ProductImageService`, jadi 5 MB adalah batas berkas yang masuk, bukan ukuran
 * berkas yang akhirnya disimpan.
 *
 * Jumlah foto per request dibatasi oleh sisa kapasitas produk, yang dihitung
 * controller. Batas di sini hanya menjaga agar request tidak mengirim ribuan
 * berkas sebelum controller sempat menghitung sisa kapasitasnya.
 */
class StoreProductImagesRequest extends FormRequest
{
    /**
     * Batas keras per request. Nilai sebenarnya dibatasi lagi oleh sisa
     * kapasitas produk di `withValidator()`.
     */
    public const MAX_FILES_PER_REQUEST = ProductImageService::MAX_IMAGES_PER_PRODUCT;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'images' => ['required', 'array', 'min:1', 'max:'.self::MAX_FILES_PER_REQUEST],
            'images.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'images.required' => 'Pilih minimal satu foto untuk diunggah.',
            'images.min' => 'Pilih minimal satu foto untuk diunggah.',
            'images.max' => 'Maksimal '.self::MAX_FILES_PER_REQUEST.' foto per sekali unggah.',
            'images.*.image' => 'Berkas yang dipilih bukan gambar.',
            'images.*.mimes' => 'Format foto harus JPG, PNG, atau WebP.',
            'images.*.max' => 'Ukuran tiap foto maksimal 5 MB.',
        ];
    }

    /**
     * Sisa kapasitas foto ikut dipakai sebagai batas `max` yang sebenarnya.
     *
     * Tanpa ini, admin yang galerinya sudah penuh akan melihat kegagalan saat
     * memproses berkas, bukan sebelum memilih berkas.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $product = $this->route('product');

            // Kalau route param bukan model, route model binding sudah menolak
            // request dengan 404 sebelum validasi berjalan, jadi tidak ada sisa
            // kapasitas yang perlu dihitung di sini.
            if (! $product instanceof Product) {
                return;
            }

            $files = $this->file('images');

            // Aturan `images` sudah mewajibkan nilai berupa array. Kalau bukan,
            // aturan itu yang melaporkan masalahnya, dan menghitungnya di sini
            // cuma menghasilkan error server.
            if (! is_array($files)) {
                return;
            }

            $slots = max(0, ProductImageService::MAX_IMAGES_PER_PRODUCT - $product->images()->count());

            if (count($files) > $slots) {
                $validator->errors()->add(
                    'images',
                    $slots === 0
                        ? 'Produk ini sudah memiliki '.ProductImageService::MAX_IMAGES_PER_PRODUCT.' foto. Hapus salah satu sebelum menambah lagi.'
                        : 'Maksimal '.$slots.' foto lagi untuk produk ini.',
                );
            }
        });
    }
}
