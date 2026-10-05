<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\Laravel\Facades\Image;
use RuntimeException;

/**
 * Kelola foto produk di disk publik (ROADMAP 4.3).
 *
 * Foto produk diunggah oleh admin dan dibaca publik lewat URL, jadi berkas
 * hasilnya selalu disimpan sebagai WebP dengan ukuran yang sudah dipotong.
 * Dua alasan berkas asli tidak disimpan apa adanya:
 *
 * 1. Foto dari kamera HP bisa berukuran 5 MB, sedangkan kartu katalog memuat
 *    belasan foto sekaligus. PRD section 33 meminta halaman katalog cepat, jadi
 *    ukuran gambarnya yang dipotong, bukan berharap browser saja yang bekerja
 *    lebih cepat.
 * 2. Foto HP membawa EXIF berisi lokasi pengambilan. Metadata itu ikut hilang
 *    saat gambar di-encode ulang ke WebP, jadi koordinat lokasi pengambilan
 *    tidak ikut tersebar lewat file publik.
 *
 * Foto utama selalu ada selama produk punya foto: foto pertama otomatis menjadi
 * foto utama, dan menghapus foto utama langsung menggantinya dengan foto
 * berikutnya. Katalog sendiri sudah punya fallback ke foto pertama
 * (`CatalogController::photo()`), jadi produk tanpa foto utama tidak membuat
 * katalog error, hanya membuat foto yang tampil tidak bisa dipilih admin.
 */
final class ProductImageService
{
    /**
     * Jumlah maksimum foto per produk.
     *
     * Delapan foto cukup untuk galeri (satu utama plus beberapa sudut), dan
     * jumlahnya dibatasi supaya halaman detail tidak berubah jadi halaman yang
     * isinya lebih berat dari isi produknya sendiri.
     */
    public const MAX_IMAGES_PER_PRODUCT = 8;

    /**
     * Sisi maksimum gambar hasil kompresi, dalam piksel.
     *
     * Katalog menampilkan foto di kartu dan di galeri detail, dan layar HP
     * umumnya lebih lebar dari 1200px dalam kondisi nyata. Memotong lebih besar
     * hanya menambah ukuran berkas tanpa pernah dipakai.
     */
    public const MAX_WIDTH = 1200;

    public const MAX_HEIGHT = 1200;

    /**
     * Kualitas WebP 0-100. Angka 80 hampir tidak terlihat bedanya dari aslinya
     * pada ukuran katalog, tapi ukuran berkasnya turun jauh dibanding JPEG
     * kualitas tinggi.
     */
    public const QUALITY = 80;

    /**
     * Folder induk foto produk pada disk `public`.
     *
     * Foto disimpan per produk di bawah folder ini, bukan disatukan, supaya
     * folder satu produk bisa dihapus utuh tanpa harus menebak berkas mana yang
     * milik produk tersebut.
     */
    public const DIRECTORY = 'products';

    /**
     * Simpan foto-foto baru untuk `$product`.
     *
     * Setiap foto ditulis ke disk lalu dibuatkan barisnya. Foto pertama pada
     * produk tanpa foto menjadi foto utama.
     *
     * Kalau ada foto yang gagal ditulis, baris dan berkas dari foto-foto yang
     * sudah tersimpan pada request yang sama ikut dibatalkan. Membatalkan
     * keduanya penting: kalau barisnya dibiarkan tapi berkasnya dihapus, katalog
     * akan menampilkan foto yang isinya 404, dan tidak ada baris `product_images`
     * yang bisa dicari untuk membersihkan sisa-sisanya. Kalau berkasnya dibiarkan
     * tapi barisnya dihapus, ada berkas yatim di disk yang tidak pernah dibaca.
     *
     * @param  list<UploadedFile>  $files
     * @return int jumlah foto yang benar-benar tersimpan
     *
     * @throws RuntimeException kalau penyimpanan gagal di tengah jalan
     */
    public function store(Product $product, array $files): int
    {
        $stored = 0;
        /** @var list<string> $writtenPaths */
        $writtenPaths = [];
        /** @var list<ProductImage> $createdImages */
        $createdImages = [];

        try {
            foreach ($files as $file) {
                $path = $this->write($product, $file);
                $writtenPaths[] = $path;

                $createdImages[] = $product->images()->create([
                    'image' => $path,
                    'is_primary' => ! $this->hasPrimary($product),
                    'sort_order' => $this->nextSortOrder($product),
                ]);

                $stored++;
            }
        } catch (\Throwable $e) {
            foreach ($createdImages as $image) {
                $image->delete();
            }

            $this->deleteFiles($writtenPaths);

            throw $e;
        }

        return $stored;
    }

    /**
     * Hapus satu foto produk beserta berkasnya.
     *
     * Berkas dihapus setelah baris datanya hilang, bukan sebelumnya. Kalau
     * hapus baris gagal, berkas masih ada dan fotonya masih tampil; kalau
     * urutannya dibalik, katalog bisa menampilkan foto yang berkasnya sudah
     * hilang.
     *
     * Foto utama yang dihapus langsung digantikan foto berikutnya supaya produk
     * tidak ditinggalkan tanpa foto utama.
     */
    public function delete(ProductImage $image): void
    {
        $product = $image->product;
        $path = $image->image;
        $wasPrimary = $image->is_primary;

        $image->delete();

        $this->deleteFiles([$path]);

        if ($wasPrimary) {
            $this->promoteNextPrimary($product);
        }
    }

    /**
     * Jadikan `$image` foto utama produk dan lepaskan status utama dari foto
     * lain.
     *
     * Penulisan dilakukan dalam satu transaksi supaya tidak pernah terlihat
     * produk dengan dua foto utama. Katalog sendiri aman terhadap dua foto
     * utama karena memakai `firstWhere('is_primary', true)`, tapi galeri di
     * halaman detail dan tampilan admin akan menampilkan dua foto yang sama-sama
     * ditandai utama, dan itu membingungkan.
     */
    public function makePrimary(ProductImage $image): void
    {
        $product = $image->product;

        $product->getConnection()->transaction(function () use ($product, $image): void {
            ProductImage::query()
                ->where('product_id', $product->getKey())
                ->where('id', '!=', $image->getKey())
                ->update(['is_primary' => false]);

            $image->update(['is_primary' => true]);
        });
    }

    /**
     * Sisa kapasitas foto untuk produk ini.
     *
     * Dipakai controller untuk membatasi jumlah berkas per request, supaya foto
     * yang kelebihan ditolak sebagai error validasi yang bisa dibaca admin,
     * bukan sebagai kesalahan server.
     */
    public function remainingSlots(Product $product): int
    {
        return max(0, self::MAX_IMAGES_PER_PRODUCT - $product->images()->count());
    }

    /**
     * Tulis satu berkas foto yang sudah dikompresi ke disk `public`.
     *
     * Nama berkas memakai UUID seperti bukti pembayaran (`BookingProofs`), jadi
     * nama asli dari perangkat admin tidak pernah menyentuh disk dan tidak bisa
     * menabrak foto lain.
     *
     * @throws RuntimeException kalau disk gagal menyimpan berkas
     */
    private function write(Product $product, UploadedFile $file): string
    {
        /** @var Filesystem $disk */
        $disk = Storage::disk('public');

        $path = self::DIRECTORY.'/'.$product->getKey().'/'.Str::uuid()->toString().'.webp';

        if ($disk->put($path, $this->encode($file)) === false) {
            throw new RuntimeException('Foto produk gagal disimpan.');
        }

        return $path;
    }

    /**
     * Baca berkasnya, potong ukuran, lalu encode ulang ke WebP.
     *
     * `scaleDown()` tidak memperbesar gambar yang ukurannya sudah kecil, jadi
     * foto beresolusi rendah tidak dilebarkan hanya untuk memenuhi batas.
     *
     * @throws RuntimeException kalau berkas tidak bisa dibaca sebagai gambar
     */
    private function encode(UploadedFile $file): string
    {
        $temporaryPath = $file->getRealPath();

        if ($temporaryPath === false) {
            throw new RuntimeException('Foto produk tidak bisa dibaca.');
        }

        try {
            $image = Image::decodePath($temporaryPath)
                ->scaleDown(self::MAX_WIDTH, self::MAX_HEIGHT)
                ->encode(new WebpEncoder(self::QUALITY, strip: true));
        } catch (\Throwable $e) {
            // Berkas lolos validasi mime tapi isinya tidak bisa dibaca, misalnya
            // unggahan yang terputus di tengah. Pesan aslinya ikut dibawa supaya
            // admin melihat penyebabnya tanpa menebak.
            throw new RuntimeException('Foto produk tidak bisa diproses: '.$e->getMessage(), previous: $e);
        }

        return (string) $image;
    }

    private function hasPrimary(Product $product): bool
    {
        return $product->images()->where('is_primary', true)->exists();
    }

    /**
     * Urutan foto berikutnya, berbasis urutan terbesar yang sudah ada.
     *
     * Foto baru selalu ditambahkan di belakang, bukan disisipkan di tengah.
     * Admin yang sudah menata galeri tidak boleh mendapat urutannya berubah
     * diam-diam setiap kali satu foto diunggah.
     *
     * Penomoran mulai dari 1. `product_images` tidak punya batasan unik pada
     * `sort_order`, jadi nomor urut ini hanya untuk mengurutkan tampilan dan tidak
     * pernah dipakai sebagai identitas.
     */
    private function nextSortOrder(Product $product): int
    {
        return (int) ($product->images()->max('sort_order') ?? 0) + 1;
    }

    /**
     * Angkat foto pertama yang tersisa menjadi foto utama.
     *
     * Dipanggil setelah foto utama dihapus. Kalau produk tidak punya foto lain,
     * produk memang tidak punya foto utama, dan katalog menampilkan placeholder
     * karena `CatalogController::photo()` mengembalikan `null`.
     */
    private function promoteNextPrimary(Product $product): void
    {
        $product->images()->orderBy('sort_order')->first()?->update(['is_primary' => true]);
    }

    /**
     * @param  list<string>  $paths
     */
    private function deleteFiles(array $paths): void
    {
        /** @var Filesystem $disk */
        $disk = Storage::disk('public');

        foreach ($paths as $path) {
            if (! str_starts_with($path, self::DIRECTORY.'/')) {
                continue;
            }

            try {
                $disk->delete($path);
            } catch (\Throwable) {
                // Berkas yang sudah hilang dianggap sudah terhapus. Kegagalan
                // menghapus berkas tidak boleh membatalkan penghapusan foto yang
                // sudah berhasil di database.
            }
        }
    }
}
