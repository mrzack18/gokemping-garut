<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Business;
use App\Support\BookingCodeGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

/**
 * BR-10: kode booking `GK-YYYYMMDD-NNN` / `SSG-YYYYMMDD-NNN`.
 *
 * Generator dibaca dari kode yang benar-benar tersimpan, jadi test ini membuat
 * booking dengan kode tertentu lalu meminta kode berikutnya. Ini yang
 * membedakan urutan canonical dari tebakan.
 */
class BookingCodeGeneratorTest extends TestCase
{
    use RefreshDatabase;

    private BookingCodeGenerator $codes;

    protected function setUp(): void
    {
        parent::setUp();

        $this->codes = new BookingCodeGenerator;
    }

    #[Test]
    public function kode_pertama_pada_suatu_tanggal_bernomor_satu(): void
    {
        $camping = Business::factory()->create(['booking_code_prefix' => 'GK']);

        $this->assertSame(
            'GK-'.now()->format('Ymd').'-001',
            $this->codes->next($camping, Carbon::now()),
        );
    }

    #[Test]
    public function nomor_urut_berikutnya_mengikuti_kode_yang_tersimpan(): void
    {
        $camping = Business::factory()->create(['booking_code_prefix' => 'GK']);
        $this->existingCode($camping, 'GK-'.now()->format('Ymd').'-001');
        $this->existingCode($camping, 'GK-'.now()->format('Ymd').'-002');

        $this->assertSame(
            'GK-'.now()->format('Ymd').'-003',
            $this->codes->next($camping, Carbon::now()),
        );
    }

    #[Test]
    public function nomor_urut_dimulai_ulang_pada_tanggal_berbeda(): void
    {
        $camping = Business::factory()->create(['booking_code_prefix' => 'GK']);
        $this->existingCode($camping, 'GK-'.now()->format('Ymd').'-007');

        $besok = Carbon::now()->addDay();

        $this->assertSame('GK-'.$besok->format('Ymd').'-001', $this->codes->next($camping, $besok));
    }

    #[Test]
    public function prefix_milik_unit_lain_tidak_saling_mengganggu(): void
    {
        $camping = Business::factory()->create(['booking_code_prefix' => 'GK']);
        $sepeda = Business::factory()->create(['booking_code_prefix' => 'SSG']);

        $this->existingCode($camping, 'GK-'.now()->format('Ymd').'-001');
        $this->existingCode($sepeda, 'SSG-'.now()->format('Ymd').'-001');

        $this->assertSame('GK-'.now()->format('Ymd').'-002', $this->codes->next($camping, Carbon::now()));
        $this->assertSame('SSG-'.now()->format('Ymd').'-002', $this->codes->next($sepeda, Carbon::now()));
    }

    #[Test]
    public function kode_yang_dibuat_di_luar_aplikasi_dihormati(): void
    {
        $camping = Business::factory()->create(['booking_code_prefix' => 'GK']);
        $this->existingCode($camping, 'GK-'.now()->format('Ymd').'-009');

        $this->assertSame(
            'GK-'.now()->format('Ymd').'-010',
            $this->codes->next($camping, Carbon::now()),
        );
    }

    #[Test]
    public function nomor_urut_empat_digit_tidak_diterjemahkan_kembali_jadi_satu_hari(): void
    {
        /**
         * Setelah 999 kode, kode empat digit mulai kalah secara leksikal dari
         * kode tiga digit. Generator harus melompati kode yang sudah terpakai,
         * bukan memakai ulang 1000.
         */
        $camping = Business::factory()->create(['booking_code_prefix' => 'GK']);
        $this->existingCode($camping, 'GK-'.now()->format('Ymd').'-998');
        $this->existingCode($camping, 'GK-'.now()->format('Ymd').'-999');
        $this->existingCode($camping, 'GK-'.now()->format('Ymd').'-1000');

        $this->assertSame(
            'GK-'.now()->format('Ymd').'-1001',
            $this->codes->next($camping, Carbon::now()),
        );
    }

    #[Test]
    public function generator_gagal_dengan_pesan_jelas_saat_semua_slot_terisi(): void
    {
        /**
         * Kode empat digit dibaca lebih rendah secara leksikal, jadi generator
         * mencoba 1000 sampai 1004. Kalau semuanya terpakai, lebih baik gagal
         * dengan pesan jelas daripada diam-diam memakai kode yang sudah ada.
         */
        $camping = Business::factory()->create(['booking_code_prefix' => 'GK']);
        $this->existingCode($camping, 'GK-'.now()->format('Ymd').'-999');

        foreach (range(1000, 1004) as $sequence) {
            $this->existingCode($camping, 'GK-'.now()->format('Ymd').'-'.$sequence);
        }

        $this->expectException(RuntimeException::class);

        $this->codes->next($camping, Carbon::now());
    }

    private function existingCode(Business $business, string $code): void
    {
        Booking::factory()->create([
            'business_id' => $business->getKey(),
            'booking_code' => $code,
        ]);
    }
}
