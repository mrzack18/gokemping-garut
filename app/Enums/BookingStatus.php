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
