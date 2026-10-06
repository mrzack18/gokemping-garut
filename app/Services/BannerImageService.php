<?php

namespace App\Services;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\Laravel\Facades\Image;
use RuntimeException;

/**
 * Gambar banner di disk publik (ROADMAP 5.4).
 *
 * Sama seperti foto produk, banner dikompresi ulang ke WebP dengan sisi
 * terpanjang 1600 piksel. Banner tampil besar di landing page dan dibaca
 * pengunjung dengan koneksi apa pun, jadi berkas asli dari perangkat admin
 * tidak pernah disimpan apa adanya. Nama berkas memakai UUID supaya nama asli
 * tidak menyentuh disk dan tidak menabrak banner lain.
 */
final class BannerImageService
{
    /**
     * Sisi maksimum gambar hasil kompresi, dalam piksel.
     */
    public const MAX_WIDTH = 1600;

    public const MAX_HEIGHT = 1600;

    /**
     * Kualitas WebP 0-100.
     */
    public const QUALITY = 80;

    /**
     * Folder induk banner pada disk `public`.
     */
    public const DIRECTORY = 'banners';

    /**
     * Simpan gambar banner milik satu unit bisnis, lalu kembalikan path
     * relatifnya, misal `banners/3/6f1c....webp`.
     *
     * @throws RuntimeException kalau berkas tidak bisa dibaca atau disk gagal menyimpan
     */
    public function store(int $businessId, UploadedFile $file): string
    {
        /** @var Filesystem $disk */
        $disk = Storage::disk('public');

        $path = self::DIRECTORY.'/'.$businessId.'/'.Str::uuid()->toString().'.webp';

        if ($disk->put($path, $this->encode($file)) === false) {
            throw new RuntimeException('Gambar banner gagal disimpan.');
        }

        return $path;
    }

    /**
     * Hapus gambar banner dari disk.
     *
     * Path kosong dan path di luar folder banner diabaikan supaya nilai rusak
     * tidak bisa membuat aplikasi menghapus berkas lain. Kegagalan menghapus
     * ditoleransi: berkas yang sudah hilang berarti tujuannya sudah tercapai.
     */
    public function delete(?string $path): void
    {
        if ($path === null || $path === '' || ! str_starts_with($path, self::DIRECTORY.'/')) {
            return;
        }

        try {
            /** @var Filesystem $disk */
            $disk = Storage::disk('public');
            $disk->delete($path);
        } catch (\Throwable) {
            // Berkas yang sudah tidak ada dianggap sudah terhapus.
        }
    }

    /**
     * Baca berkasnya, potong ukuran, lalu encode ulang ke WebP.
     *
     * @throws RuntimeException kalau berkas tidak bisa dibaca sebagai gambar
     */
    private function encode(UploadedFile $file): string
    {
        $temporaryPath = $file->getRealPath();

        if ($temporaryPath === false) {
            throw new RuntimeException('Gambar banner tidak bisa dibaca.');
        }

        try {
            $image = Image::decodePath($temporaryPath)
                ->scaleDown(self::MAX_WIDTH, self::MAX_HEIGHT)
                ->encode(new WebpEncoder(self::QUALITY, strip: true));
        } catch (\Throwable $e) {
            throw new RuntimeException('Gambar banner tidak bisa diproses: '.$e->getMessage(), previous: $e);
        }

        return (string) $image;
    }
}
