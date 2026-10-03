<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case BelumDibayar = 'belum_dibayar';
    case MenungguVerifikasi = 'menunggu_verifikasi';
    case Lunas = 'lunas';
    case Ditolak = 'ditolak';

    /**
     * Cash tidak melalui tahap verifikasi bukti, sehingga saat pembayaran
     * dicatat statusnya langsung menjadi lunas.
     */
    public function initialFor(PaymentMethodType $method): self
    {
        return match ($method) {
            PaymentMethodType::Cash => self::BelumDibayar,
            PaymentMethodType::Qris, PaymentMethodType::BankTransfer => self::MenungguVerifikasi,
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::BelumDibayar => in_array($target, [self::MenungguVerifikasi, self::Lunas], true),
            self::MenungguVerifikasi => in_array($target, [self::Lunas, self::Ditolak], true),
            self::Lunas, self::Ditolak => false,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::BelumDibayar => 'Belum Dibayar',
            self::MenungguVerifikasi => 'Menunggu Verifikasi',
            self::Lunas => 'Lunas',
            self::Ditolak => 'Ditolak',
        };
    }
}
