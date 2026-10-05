<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductStatusRequest;
use App\Http\Requests\StoreProductImagesRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Business;
use App\Models\Category;
use App\Models\Product;
use App\Services\ProductImageService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Manajemen produk (PRD section 23, ROADMAP 4.3).
 *
 * Daftar produk di sini berbeda dari katalog publik dalam tiga hal yang penting:
 * produk nonaktif ikut tampil (katalog menyembunyikannya), pencarian tidak
 * dibatasi ke produk aktif, dan produk yang sudah di-soft-delete tidak ikut
 * tampil karena produk yang dihapus memang harus hilang dari tempat kerja admin.
 *
 * Seluruh query ter-scope ke `business_id` admin yang login lewat `BusinessScope`,
 * dan filter kategori memakai id kategori milik unit ini juga, jadi admin tidak
 * bisa memfilter dengan kategori milik unit lain.
 */
class ProductController extends Controller
{
    private const PER_PAGE = 15;

    private const MAX_SEARCH_LENGTH = 100;

    /**
     * Daftar produk dengan pencarian dan filter kategori dan status.
     */
    public function index(Request $request): Response
    {
        $business = $request->user()->business;
        $filters = $this->filters($request);

        return Inertia::render('admin/products/index', [
            'products' => $this->products($business, $filters),
            'categories' => $this->categories($business),
            'filters' => $filters,
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('admin/products/create', [
            'categories' => $this->categories($request->user()->business),
            'priceUnits' => StoreProductRequest::PRICE_UNITS,
            'maxImages' => ProductImageService::MAX_IMAGES_PER_PRODUCT,
        ]);
    }

    public function store(StoreProductRequest $request, ProductImageService $photos): RedirectResponse
    {
        $business = $request->user()->business;

        $product = Product::create([
            ...$request->productPayload(),
            'business_id' => $business->getKey(),
        ]);

        /**
         * Foto diunggah dari form yang sama supaya admin tidak perlu menyimpan
         * produk dulu, membuka halaman edit, lalu mengunggah foto. Penyimpanan
         * produk sudah selesai sebelum foto diproses, jadi kalau kompresi foto
         * gagal, produknya tetap ada di daftar dan admin tinggal mengunggah
         * ulang fotonya.
         */
        $files = $this->uploadedFiles($request);

        if ($files !== []) {
            $photos->store($product, $files);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Produk "'.$product->name.'" berhasil ditambahkan.',
        ]);

        return to_route('admin.products.edit', $product);
    }

    public function edit(Request $request, Product $product): Response
    {
        $product->loadMissing(['category', 'images']);

        return Inertia::render('admin/products/edit', [
            'product' => [
                'id' => (int) $product->getKey(),
                'name' => $product->name,
                'slug' => $product->slug,
                'category_id' => $product->category_id === null ? null : (int) $product->category_id,
                'description' => $product->description,
                'specification' => $this->specification($product->specification ?? []),
                'rental_terms' => $product->rental_terms,
                'price' => $product->price,
                'price_unit' => $product->price_unit,
                'stock' => $product->stock,
                'is_active' => $product->is_active,
                'photos' => $product->images
                    ->map(fn ($image): array => [
                        'id' => (int) $image->getKey(),
                        'url' => $image->url,
                        'is_primary' => $image->is_primary,
                    ])
                    ->values()
                    ->all(),
                'bookingCount' => $product->bookingItems()->count(),
            ],
            'categories' => $this->categories($request->user()->business),
            'priceUnits' => StoreProductRequest::PRICE_UNITS,
            'maxImages' => ProductImageService::MAX_IMAGES_PER_PRODUCT,
            'remainingImages' => max(0, ProductImageService::MAX_IMAGES_PER_PRODUCT - $product->images->count()),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $product->update($request->productPayload());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Produk "'.$product->name.'" berhasil diperbarui.',
        ]);

        return to_route('admin.products.edit', $product);
    }

    /**
     * Ubah status aktif dari daftar produk.
     *
     * Status produk punya arti untuk katalog publik, jadi aksi ini harus jelas
     * bedanya dengan menghapus produk: produk yang dinonaktifkan masih bisa
     * dipilih admin, produk yang dihapus tidak muncul lagi di halaman admin.
     */
    public function updateStatus(ProductStatusRequest $request, Product $product): RedirectResponse
    {
        $isActive = $request->boolean('is_active');

        $product->update(['is_active' => $isActive]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Produk "'.$product->name.'" berhasil '.($isActive ? 'diaktifkan' : 'dinonaktifkan').'.',
        ]);

        return back();
    }

    /**
     * Soft delete produk.
     *
     * Baris produk dan fotonya tidak dihapus permanen karena `booking_items`
     * menunjuk produk ini dan isinya harus tetap terbaca sebagai riwayat. Harga
     * dan nama produk sudah disalin ke `booking_items` saat transaksi dibuat
     * (BR-09), jadi produk yang hilang tidak merusak booking yang sudah lewat.
     *
     * Foto juga tidak dihapus dari disk supaya produk masih bisa dipulihkan tanpa
     * mengunggah ulang foto. Pemulihan belum ada di halaman admin; yang ada
     * sekarang baru penghapusan, dan `products.deleted_at` sudah menyediakan
     * kolomnya.
     */
    public function destroy(Product $product): RedirectResponse
    {
        $name = $product->name;
        $bookingCount = $product->bookingItems()->count();

        $product->update(['is_active' => false]);
        $product->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $bookingCount === 0
                ? 'Produk "'.$name.'" berhasil dihapus.'
                : 'Produk "'.$name.'" dihapus dari daftar. '.$bookingCount.' booking memakai produk ini dan riwayatnya tetap tersimpan.',
        ]);

        return to_route('admin.products.index');
    }

    /**
     * Unggah foto produk dari panel galeri di halaman edit.
     *
     * Form foto terpisah dari form produk supaya mengunggah foto tidak ikut
     * mengirim ulang isian produk yang belum disimpan.
     */
    public function storeImages(
        StoreProductImagesRequest $request,
        Product $product,
        ProductImageService $photos,
    ): RedirectResponse {
        $stored = $photos->store($product, $this->uploadedFiles($request));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $stored === 1
                ? '1 foto berhasil diunggah.'
                : $stored.' foto berhasil diunggah.',
        ]);

        return to_route('admin.products.edit', $product);
    }

    /**
     * @param  array{q: string, category: int|null, status: string}  $filters
     * @return LengthAwarePaginator<int, array{
     *     id: int,
     *     name: string,
     *     slug: string,
     *     category: string|null,
     *     price: int,
     *     price_label: string,
     *     price_unit: string,
     *     stock: int,
     *     is_active: bool,
     *     is_available: bool,
     *     photo: string|null,
     *     images_count: int,
     *     updated_at_label: string
     * }>
     */
    private function products(Business $business, array $filters): LengthAwarePaginator
    {
        $query = Product::query()
            ->forBusiness($business)
            ->with(['category', 'images']);

        if ($filters['q'] !== '') {
            $term = '%'.addcslashes($filters['q'], '\\%_').'%';

            $query->where(fn (Builder $builder) => $builder
                ->where('products.name', 'like', $term)
                // Deskripsi ikut dicari supaya admin bisa menemukan produk dari
                // kata yang hanya tertulis di deskripsi. Spesifikasi tidak ikut
                // dicari: isinya disimpan sebagai objek JSON dengan label bebas,
                // jadi mencarinya berarti menelusuri teks tanpa tahu kuncinya.
                ->orWhere('products.description', 'like', $term)
            );
        }

        if ($filters['category'] !== null) {
            $query->where('products.category_id', $filters['category']);
        }

        if ($filters['status'] === 'aktif') {
            $query->where('products.is_active', true);
        } elseif ($filters['status'] === 'nonaktif') {
            $query->where('products.is_active', false);
        }

        return $query
            ->orderBy('products.name')
            ->paginate(self::PER_PAGE)
            ->withQueryString()
            ->through(fn (Product $product): array => $this->row($product));
    }

    /**
     * Satu baris daftar produk.
     *
     * Harga dan stok dikembalikan dua kali dengan format berbeda: angka untuk
     * form edit, dan teks siap tampil untuk tabel daftar. Formatter dipindah ke
     * backend supaya daftar, ringkasan, dan katalog memakai angka yang sama dan
     * tidak bisa berbeda karena format di dua tempat.
     *
     * @return array{
     *     id: int,
     *     name: string,
     *     slug: string,
     *     category: string|null,
     *     price: int,
     *     price_label: string,
     *     price_unit: string,
     *     stock: int,
     *     is_active: bool,
     *     is_available: bool,
     *     photo: string|null,
     *     images_count: int,
     *     updated_at_label: string
     * }
     */
    private function row(Product $product): array
    {
        return [
            'id' => (int) $product->getKey(),
            'name' => $product->name,
            'slug' => $product->slug,
            'category' => $product->category?->name,
            'price' => (int) $product->price,
            'price_label' => 'Rp'.number_format((int) $product->price, 0, ',', '.'),
            'price_unit' => $product->price_unit,
            'stock' => (int) $product->stock,
            'is_active' => $product->is_active,
            // Stok 0 berarti produk masih tampil di katalog tapi tidak bisa
            // disewa. Definisi ini sama dengan `CatalogController`, supaya admin
            // dan pengunjung melihat status ketersediaan yang sama.
            'is_available' => $product->stock > 0,
            'photo' => $this->photo($product),
            'images_count' => $product->images->count(),
            'updated_at_label' => $product->updated_at?->format('d M Y') ?? '-',
        ];
    }

    /**
     * Foto utama produk dengan aturan yang sama persis dengan katalog: gambar
     * berflag `is_primary`, atau gambar pertama menurut urutan kalau tidak ada
     * yang ditandai.
     */
    private function photo(Product $product): ?string
    {
        $primary = $product->images->firstWhere('is_primary', true);

        return ($primary ?? $product->images->first())?->url;
    }

    /**
     * Spesifikasi disimpan sebagai objek JSON dan dikirim sebagai baris
     * label-isi ke form edit.
     *
     * Urutan baris mengikuti urutan key di JSON. MySQL menormalkan urutan key pada
     * kolom JSON, jadi urutan yang tampil mungkin bukan urutan saat admin mengetik.
     * Yang dijaga di sini cuma satu: urutan yang dikirim balik sama persis dengan
     * urutan yang dimuat, sehingga baris-baris ini tidak saling bertukar tempat
     * setiap kali produk disimpan ulang.
     *
     * @param  array<int|string, mixed>  $specification
     * @return list<array{key: string, value: string}>
     */
    private function specification(array $specification): array
    {
        $rows = [];

        foreach ($specification as $key => $value) {
            // Key numerik tetap mungkin terjadi: JSON seperti `{"0":"isi"}`
            // dibaca PHP sebagai array dengan key integer, dan key seperti itu
            // tidak bisa dipakai sebagai label di form.
            if (! is_string($key) || trim($key) === '') {
                continue;
            }

            $rows[] = [
                'key' => $key,
                'value' => is_scalar($value) ? (string) $value : '',
            ];
        }

        return $rows;
    }

    /**
     * @return list<array{id: int, name: string, products_count: int}>
     */
    private function categories(Business $business): array
    {
        $categories = [];

        $query = Category::query()
            ->forBusiness($business)
            ->withCount('products')
            ->orderBy('sort_order')
            ->orderBy('name');

        foreach ($query->get() as $category) {
            $categories[] = [
                'id' => (int) $category->getKey(),
                'name' => $category->name,
                'products_count' => (int) $category->products_count,
            ];
        }

        return $categories;
    }

    /**
     * State filter dibaca dari query string supaya daftar bisa di-share dan
     * filter bertahan saat pindah halaman.
     *
     * Nilai yang tidak valid diabaikan, bukan dibalas 422. Daftar produk adalah
     * halaman kerja, bukan form, dan admin tidak boleh terkunci karena URL yang
     * tersalin tidak lengkap.
     *
     * @return array{q: string, category: int|null, status: string}
     */
    private function filters(Request $request): array
    {
        $search = $request->query('q');
        $status = $request->query('status');

        return [
            'q' => is_string($search)
                ? Str::limit(trim($search), self::MAX_SEARCH_LENGTH, '')
                : '',
            'category' => $this->positiveInt($request->query('category')),
            'status' => is_string($status) && in_array($status, ['aktif', 'nonaktif'], true)
                ? $status
                : 'semua',
        ];
    }

    private function positiveInt(mixed $value): ?int
    {
        $parsed = match (true) {
            is_int($value) => $value,
            is_string($value) && preg_match('/^\d+$/', $value) === 1 => (int) $value,
            default => null,
        };

        return $parsed !== null && $parsed > 0 ? $parsed : null;
    }

    /**
     * @return list<UploadedFile>
     */
    private function uploadedFiles(Request $request): array
    {
        $files = $request->file('images');

        if ($files instanceof UploadedFile) {
            return [$files];
        }

        return is_array($files) ? array_values($files) : [];
    }
}
