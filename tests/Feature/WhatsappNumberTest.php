<?php

namespace Tests\Feature;

use App\Support\WhatsappNumber;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class WhatsappNumberTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function normalizableNumbers(): array
    {
        return [
            'format 62' => ['628123456789', '628123456789'],
            'format lokal 0' => ['08123456789', '628123456789'],
            'format lokal 0 dengan spasi' => ['0812 3456 789', '628123456789'],
            'format lokal 0 dengan tanda hubung' => ['0812-3456-789', '628123456789'],
            'dengan kode negara plus' => ['+62 812-3456-789', '628123456789'],
            'dengan tanda kurung' => ['(+62) 812 3456 789', '628123456789'],
        ];
    }

    #[DataProvider('normalizableNumbers')]
    public function test_nomor_whatsapp_dinormalkan(string $input, string $expected): void
    {
        $this->assertSame($expected, WhatsappNumber::normalize($input));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function invalidNumbers(): array
    {
        return [
            'kosong' => [''],
            'hanya spasi' => ['   '],
            'huruf saja' => ['bukan nomor'],
            'tanpa kode negara' => ['1234567890'],
            'terlalu pendek setelah 62' => ['62812345'],
            'kode negara lain' => ['+14155552671'],
        ];
    }

    #[DataProvider('invalidNumbers')]
    public function test_nomor_tidak_valid_menghasilkan_null(string $input): void
    {
        $this->assertNull(WhatsappNumber::normalize($input));
    }

    public function test_nomor_sudah_ter_normalisasi_tidak_berubah(): void
    {
        $this->assertSame('628123456789', WhatsappNumber::normalize('628123456789'));
    }

    public function test_normalisasi_bersifat_idempoten(): void
    {
        $once = WhatsappNumber::normalize('0812-3456-789');

        $this->assertNotNull($once);
        $this->assertSame($once, WhatsappNumber::normalize($once));
    }
}
