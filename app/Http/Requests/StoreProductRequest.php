<?php

namespace App\Http\Requests;

use App\Models\Business;
use App\Models\Product;
use App\Services\ProductImageService;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validasi penyimpanan produk baru (ROADMAP 4.3).
 *
 * `business_id` tidak pernah diambil dari request, dan `slug` juga tidak: slug
 * dibuat dari nama lewat `Product::generateSlug()` supaya nama yang sama di dua
 * unit tidak bentrok dan slug yang terlarang untuk path publik otomatis
 * dihindari.
 *
 * Kategori wajib dipilih. Katalog mengelompokkan dan memfilter produk
 * berdasarkan kategori, jadi produk tanpa kategori tidak muncul di filter mana
 * pun dan hanya bisa ditemukan lewat pencarian. Kolom `products.category_id`
 * tetap nullable supaya data lama tidak ikut rusak.
 */
class StoreProductRequest extends FormRequest
{
    /**
     * Satuan harga yang dipakai di katalog.
     *
     * Daftar pendek ini mencegah salah ketik satuan yang langsung tampil di
     * kartu katalog, misalnya "harii". Menambah satuan baru cukup menambahkan
     * nilai di sini.
     *
     * @var list<string>
     */
    public const PRICE_UNITS = ['hari', 'jam', 'paket', 'event'];

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Aturan lengkap form tambah: isian produk plus foto.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...$this->productRules(categoryRequired: true),
            ...$this->photoRules(),
        ];
    }

    /**
     * Aturan isian produknya saja, dipisah dari aturan foto supaya subclass
     * bisa memakai aturan foto atau tidak.
     *
     * @return array<string, mixed>
     */
    protected function productRules(bool $categoryRequired): array
    {
        return [
            'category_id' => $this->categoryRule($categoryRequired),
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'specification' => ['nullable', 'array', 'max:20'],
            'specification.*.key' => ['nullable', 'string', 'max:60'],
            'specification.*.value' => ['nullable', 'string', 'max:200'],
            'rental_terms' => ['nullable', 'string', 'max:2000'],
            'price' => ['required', 'integer', 'min:0', 'max:100000000'],
            'price_unit' => ['required', 'string', Rule::in(self::PRICE_UNITS)],
            'stock' => ['required', 'integer', 'min:0', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Aturan foto produk pada form tambah.
     *
     * Batas mime dan ukuran mengikuti BR-08 supaya aturan unggah konsisten dengan
     * bukti pembayaran. Bedanya, foto produk dikompresi ulang oleh
     * `ProductImageService`, jadi 5 MB adalah ukuran berkas yang masuk, bukan
     * ukuran berkas yang akhirnya disimpan.
     *
     * Foto bersifat opsional supaya produk tetap bisa disimpan walau admin belum
     * punya foto yang siap. Katalog sudah menangani produk tanpa foto dengan
     * placeholder.
     *
     * @return array<string, mixed>
     */
    protected function photoRules(): array
    {
        return [
            'images' => ['nullable', 'array', 'max:'.ProductImageService::MAX_IMAGES_PER_PRODUCT],
            'images.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'category_id.required' => 'Pilih kategori produk.',
            'category_id.exists' => 'Kategori yang dipilih tidak tersedia.',
            'name.required' => 'Nama produk wajib diisi.',
            'name.max' => 'Nama produk maksimal 100 karakter.',
            'description.max' => 'Deskripsi produk maksimal 2000 karakter.',
            'specification.max' => 'Spesifikasi maksimal 20 baris.',
            'specification.*.key.max' => 'Label spesifikasi maksimal 60 karakter.',
            'specification.*.value.max' => 'Isi spesifikasi maksimal 200 karakter.',
            'rental_terms.max' => 'Ketentuan penyewaan maksimal 2000 karakter.',
            'price.required' => 'Harga sewa wajib diisi.',
            'price.integer' => 'Harga sewa harus berupa angka bulat.',
            'price.min' => 'Harga sewa tidak boleh negatif.',
            'price.max' => 'Harga sewa maksimal 100.000.000.',
            'price_unit.required' => 'Satuan harga wajib dipilih.',
            'price_unit.in' => 'Satuan harga harus salah satu dari: '.implode(', ', self::PRICE_UNITS).'.',
            'stock.required' => 'Jumlah stok wajib diisi.',
            'stock.integer' => 'Stok harus berupa angka bulat.',
            'stock.min' => 'Stok tidak boleh negatif.',
            'stock.max' => 'Stok maksimal 1000.',
            'images.max' => 'Maksimal '.ProductImageService::MAX_IMAGES_PER_PRODUCT.' foto per sekali unggah.',
            'images.*.image' => 'Berkas yang dipilih bukan gambar.',
            'images.*.mimes' => 'Format foto harus JPG, PNG, atau WebP.',
            'images.*.max' => 'Ukuran tiap foto maksimal 5 MB.',
        ];
    }

    /**
     * Baris spesifikasi harus utuh: label dan isi keduanya terisi, atau keduanya
     * kosong, dan labelnya tidak boleh sama antarbaris.
     *
     * Baris kosong dibiarkan lewat supaya admin yang menyisakan baris terakhir tidak
     * tersulit oleh validasi, dan baris setengah terisi ditolak supaya isi yang
     * diketik tidak hilang diam-diam.
     *
     * Label ganda juga ditolak. Spesifikasi disimpan sebagai objek JSON yang
     * kuncinya adalah label, jadi dua baris dengan label sama akan saling menimpa
     * dan satu baris hilang tanpa ada yang menyadarinya.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $rows = $this->input('specification');

            if (! is_array($rows)) {
                return;
            }

            /** @var array<string, list<int>> $labels */
            $labels = [];

            foreach ($rows as $index => $row) {
                if (! is_array($row)) {
                    continue;
                }

                $key = trim((string) ($row['key'] ?? ''));
                $value = trim((string) ($row['value'] ?? ''));

                if ($key === '' && $value === '') {
                    continue;
                }

                if ($key === '' || $value === '') {
                    $field = $key === '' ? "specification.{$index}.key" : "specification.{$index}.value";

                    $validator->errors()->add(
                        $field,
                        'Baris spesifikasi harus diisi lengkap: label dan isi wajib diisi.',
                    );

                    continue;
                }

                $labels[$key][] = $index;
            }

            $duplicated = array_keys(array_filter($labels, fn (array $indexes) => count($indexes) > 1));

            if ($duplicated !== []) {
                $validator->errors()->add(
                    'specification',
                    'Label spesifikasi harus unik. Dipakai lebih dari sekali: "'
                        .implode('", "', $duplicated)
                        .'".',
                );
            }
        });
    }

    /**
     * Atribut siap disimpan.
     *
     * @return array<string, mixed>
     */
    public function productPayload(): array
    {
        $business = $this->business();
        $name = trim((string) $this->input('name'));

        return [
            'category_id' => $this->categoryId(),
            'name' => $name,
            'slug' => Product::generateSlug($business, $name),
            'description' => $this->text('description'),
            'specification' => $this->specification(),
            'rental_terms' => $this->text('rental_terms'),
            'price' => (int) $this->input('price'),
            'price_unit' => (string) $this->input('price_unit'),
            'stock' => (int) $this->input('stock'),
            'is_active' => $this->has('is_active') ? $this->boolean('is_active') : true,
        ];
    }

    /**
     * Id kategori hasil simpan.
     *
     * Form edit boleh tidak memilih kategori sama sekali, dan input kosong dari
     * `<select>` datang sebagai string kosong. String kosong itu harus jadi `null`,
     * bukan `0`: `0` bukan id kategori yang ada, jadi kolom `category_id` yang
     * sekarang menyimpan `0` akan ditolak constraint databasenya.
     */
    protected function categoryId(): ?int
    {
        $categoryId = $this->input('category_id');

        if ($categoryId === null || $categoryId === '') {
            return null;
        }

        return (int) $categoryId;
    }

    /**
     * Baris label dan nilai disusun menjadi objek `{label: isi}` untuk kolom
     * JSON, dan baris kosong dibuang.
     *
     * @return array<string, string>|null
     */
    public function specification(): ?array
    {
        $rows = $this->input('specification');

        if (! is_array($rows)) {
            return null;
        }

        $specification = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $key = trim((string) ($row['key'] ?? ''));
            $value = trim((string) ($row['value'] ?? ''));

            if ($key === '' || $value === '') {
                continue;
            }

            $specification[$key] = $value;
        }

        return $specification === [] ? null : $specification;
    }

    protected function business(): Business
    {
        /** @var Business $business */
        $business = $this->user()->business;

        return $business;
    }

    /**
     * Aturan kategori: wajib ada (untuk create) dan harus milik unit bisnis ini.
     *
     * Batas `business_id` di sini yang menolak request-nya.
     * `products.category_id` hanya punya batasan FK di database, tanpa aturan
     * tenant, jadi tanpa batas eksplisit kategori milik unit lain bisa lolos
     * validasi `exists`.
     *
     * @return array<int, mixed>
     */
    protected function categoryRule(bool $required): array
    {
        return [
            $required ? 'required' : 'nullable',
            'integer',
            Rule::exists('categories', 'id')->where(
                fn (Builder $query) => $query->where('business_id', $this->user()->business_id),
            ),
        ];
    }

    protected function text(string $key): ?string
    {
        $value = $this->input($key);

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
