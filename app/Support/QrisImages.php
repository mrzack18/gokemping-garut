<?php

namespace App\Support;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Gambar QRIS merchant pada disk publik (PRD section 27, ROADMAP 4.7).
 *
 * Gambar ini ditampilkan di halaman pembayaran publik saat penyewa memilih
 * QRIS, jadi berkasnya harus bisa dibaca lewat URL. Sama seperti bukti
 * pembayaran, nama berkas memakai UUID supaya nama asli dari perangkat admin
 * tidak pernah menyentuh disk, dan berkasnya dikelompokkan per unit bisnis.
 *
 * Berbeda dari foto produk, gambar QRIS tidak dikompresi ulang. Kode QR harus
 * tetap tajam saat dipindai dari layar, dan ukurannya sudah kecil karena
 * asalnya dari aplikasi merchant. Yang penting justru gambar diganti: QRIS
 * merchant lama tidak boleh tertinggal setelah ada yang baru.
 */
final class QrisImages
{
    /**
     * Folder induk gambar QRIS pada disk `public`.
     */
    public const DIRECTORY = 'qris';

    /**
     * Simpan berkas QRIS milik satu unit bisnis, lalu kembalikan path
     * relatifnya, misal `qris/3/6f1c....png`.
     *
     * @throws RuntimeException kalau disk gagal menyimpan berkas
     */
    public static function store(UploadedFile $file, int $businessId): string
    {
        /** @var Filesystem $disk */
        $disk = Storage::disk('public');

        $path = $disk->putFile(self::DIRECTORY.'/'.$businessId, $file);

        if ($path === false) {
            throw new RuntimeException('Gambar QRIS gagal disimpan.');
        }

        return $path;
    }

    /**
     * Hapus gambar QRIS dari disk.
     *
     * Path kosong dan path di luar folder QRIS diabaikan supaya nilai rusak
     * tidak bisa membuat aplikasi menghapus berkas lain. Kegagalan menghapus
     * ditoleransi: berkas yang sudah hilang berarti tujuannya sudah tercapai.
     */
    public static function delete(?string $path): void
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
}
