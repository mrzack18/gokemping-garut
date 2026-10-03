<?php

namespace App\Enums;

enum PaymentMethodType: string
{
    case Cash = 'cash';
    case Qris = 'qris';
    case BankTransfer = 'bank_transfer';

    /**
     * Bukti pembayaran wajib untuk QRIS dan transfer, opsional untuk cash (BR-08).
     */
    public function requiresProof(): bool
    {
        return match ($this) {
            self::Cash => false,
            self::Qris, self::BankTransfer => true,
        };
    }

    public function requiresVerification(): bool
    {
        return $this->requiresProof();
    }

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Cash',
            self::Qris => 'QRIS',
            self::BankTransfer => 'Transfer Bank',
        };
    }

    /**
     * @return list<self>
     */
    public static function all(): array
    {
        return [self::Cash, self::Qris, self::BankTransfer];
    }
}
