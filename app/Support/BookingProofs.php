<?php

namespace App\Support;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Penyimpanan bukti pembayaran di disk publik (ROADMAP 3.10, BR-08).
 *
 * Bukti disimpan sebelum booking-nya ada di database, karena di 3.10 penyewa
 * masih berhenti di halaman pembayaran sementara record `payments` baru dibuat
 * di ROADMAP 3.11. Jadi sementara path-nya dibawa di draft, lalu dipindahkan ke
 * kolom `payments.proof` ketika booking disimpan.
 *
 * Nama berkas memakai UUID supaya nama asli dari perangkat penyewa tidak pernah
 * menyentuh disk. Dengan begitu nama berkas tidak bisa menabrak nama lain, dan
 * path yang tersimpan tidak pernah bergantung pada apa yang diketik pengguna.
 *
 * Disk `public` dipilih karena bukti harus bisa dibaca admin lewat URL, dan
 * ketiga accessor model (`Payment::proofUrl`, `PaymentMethod::qrisImageUrl`,
 * `ProductImage::imageUrl`) sudah memakai disk yang sama. Disk default aplikasi
 * adalah `local` yang sifatnya private, jadi disk selalu disebutkan eksplisit.
 */
final class BookingProofs
{
    /**
     * Folder tempat seluruh bukti pembayaran disimpan pada disk `public`.
     */
    public const DIRECTORY = 'payments';

    /**
     * Simpan berkas bukti lalu kembalikan path relatifnya, misal
     * `payments/6f1c....jpg`.
     *
     * @throws RuntimeException kalau disk gagal menyimpan berkas
     */
    public static function store(UploadedFile $file): string
    {
        /** @var Filesystem $disk */
        $disk = Storage::disk('public');

        $path = $disk->putFile(self::DIRECTORY, $file);

        /**
         * `putFile()` mengembalikan `false` kalau penulisan gagal, misalnya
         * folder tidak writable. Nilai itu tidak boleh lolos ke draft karena
         * akan tersimpan sebagai path bukan nama berkas, dan penyewa akan
         * melihat bukti "terunggah" padahal berkasnya tidak pernah ada.
         */
        if ($path === false) {
            throw new RuntimeException('Bukti pembayaran gagal disimpan.');
        }

        return $path;
    }

    /**
     * Hapus berkas bukti dari disk.
     *
     * Path kosong dan path di luar folder bukti diabaikan supaya nilai yang
     * rusak tidak bisa membuat aplikasi menghapus berkas lain. Kegagalan
     * menghapus juga ditoleransi: berkasnya sudah hilang berarti tujuannya sudah
     * tercapai, dan penyewa tidak boleh terjebak di halaman pembayaran hanya
     * karena berkas lamanya tidak bisa dihapus.
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

    /**
     * URL publik dari sebuah path bukti, atau `null` kalau belum ada bukti.
     */
    public static function url(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        /** @var Filesystem $disk */
        $disk = Storage::disk('public');

        return $disk->url($path);
    }
}
