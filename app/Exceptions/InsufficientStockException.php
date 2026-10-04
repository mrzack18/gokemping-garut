<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Stok produk tidak cukup lagi ketika booking disimpan.
 *
 * Pengecekan stok pernah dibuat berkali-kali di sepanjang alur publik, jadi
 * kondisi ini normal dan bukan kegagalan sistem. Exception ini dipisahkan dari
 * `RuntimeException` biasa supaya controller bisa mengembalikan penyewa ke
 * formulir dengan pesan yang tepat, bukan menampilkan halaman error 500.
 */
class InsufficientStockException extends RuntimeException
{
    public function __construct(
        public readonly int $available,
        public readonly int $requested,
    ) {
        parent::__construct('Stok tidak cukup lagi untuk periode yang dipilih.');
    }
}
