<?php

namespace App\Enums;

enum BookingStatus: string
{
    case MenungguKonfirmasi = 'menunggu_konfirmasi';
    case Dikonfirmasi = 'dikonfirmasi';
    case SedangDisewa = 'sedang_disewa';
    case Selesai = 'selesai';
    case Dibatalkan = 'dibatalkan';

    /**
     * Status yang menahan stok selama periode sewa.
     *
     * Booking yang sudah selesai atau dibatalkan tidak lagi menahan
     * barang, sehingga tidak boleh dihitung saat pengecekan ketersediaan.
     */
    public function holdsStock(): bool
    {
        return match ($this) {
            self::MenungguKonfirmasi, self::Dikonfirmasi, self::SedangDisewa => true,
            self::Selesai, self::Dibatalkan => false,
        };
    }

    /**
     * Urutan alur status yang sah, dipakai untuk memvalidasi transisi status.
     */
    public function next(): ?self
    {
        return match ($this) {
            self::MenungguKonfirmasi => self::Dikonfirmasi,
            self::Dikonfirmasi => self::SedangDisewa,
            self::SedangDisewa => self::Selesai,
            self::Selesai, self::Dibatalkan => null,
        };
    }

    /**
     * Apakah status ini boleh berubah menjadi `$target`.
     *
     * Alur maju hanya boleh satu tahap, mengikuti urutan di PRD section 21:
     * menunggu → dikonfirmasi → sedang disewa → selesai. Melompati tahap
     * berarti admin menyatakan barang sudah kembali tanpa pernah menandai
     * sedang disewa, dan membuat halaman riwayat menyesatkan.
     *
     * Mundur juga ditutup. Status booking bukan isian bebas: backend katalog
     * memakai statusnya untuk menghitung stok terpakai, jadi status yang
     * dikembalikan bisa membuat barang yang sedang disewa terbaca tersedia
     * tanpa barangnya benar-benar kembali.
     *
     * Pembatalan jadi satu-satunya pengecualian: dari status mana pun yang
     * belum selesai, booking boleh dibatalkan karena kondisi transaksi bisa
     * berubah kapan saja, termasuk setelah barang sempat diambil penyewa.
     */
    public function canTransitionTo(self $target): bool
    {
        if ($target === self::Dibatalkan) {
            return $this->holdsStock();
        }

        return $this->next() === $target;
    }

    /**
     * Status ini masih bisa dibatalkan oleh admin.
     */
    public function isCancellable(): bool
    {
        return $this->canTransitionTo(self::Dibatalkan);
    }

    public function label(): string
    {
        return match ($this) {
            self::MenungguKonfirmasi => 'Menunggu Konfirmasi',
            self::Dikonfirmasi => 'Dikonfirmasi',
            self::SedangDisewa => 'Sedang Disewa',
            self::Selesai => 'Selesai',
            self::Dibatalkan => 'Dibatalkan',
        };
    }

    /**
     * @return list<self>
     */
    public static function flow(): array
    {
        return [
            self::MenungguKonfirmasi,
            self::Dikonfirmasi,
            self::SedangDisewa,
            self::Selesai,
        ];
    }
}
