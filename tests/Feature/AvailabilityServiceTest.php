<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Business;
use App\Models\Product;
use App\Models\User;
use App\Services\AvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvailabilityServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Stok 5 unit, dipakai 3 unit pada periode 10 sampai 13 Oktober, sehingga
     * harus tersisa 2 unit. PRD section 12 memakai contoh angka yang sama.
     */
    public function test_unit_tersedia_dihitung_dari_stok_kurang_stok_terpakai(): void
    {
        $business = $this->business('gokemping');
        $product = $this->product($business, ['stock' => 5]);

        $this->booking($business, $product, 3, '2026-10-10', '2026-10-13');

        $service = app(AvailabilityService::class);

        $this->assertSame(3, $service->usedUnits($product, '2026-10-10', '2026-10-12'));
        $this->assertSame(2, $service->availableUnits($product, '2026-10-10', '2026-10-12'));
    }

    public function test_booking_di_periode_lain_tidak_mengurangi_stok(): void
    {
        $business = $this->business('gokemping');
        $product = $this->product($business, ['stock' => 5]);

        $this->booking($business, $product, 3, '2026-10-10', '2026-10-13');

        $service = app(AvailabilityService::class);

        $this->assertSame(0, $service->usedUnits($product, '2026-10-13', '2026-10-15'));
        $this->assertSame(5, $service->availableUnits($product, '2026-10-13', '2026-10-15'));
    }

    public function test_booking_yang_tidak_beririsan_tidak_dihitung(): void
    {
        $business = $this->business('gokemping');
        $product = $this->product($business, ['stock' => 5]);

        $this->booking($business, $product, 2, '2026-10-01', '2026-10-05');
        $this->booking($business, $product, 2, '2026-10-20', '2026-10-25');

        $service = app(AvailabilityService::class);

        $this->assertSame(0, $service->usedUnits($product, '2026-10-06', '2026-10-19'));
    }

    public function test_booking_yang_menutup_dan_membuka_periode_tetap_dihitung(): void
    {
        $business = $this->business('gokemping');
        $product = $this->product($business, ['stock' => 5]);

        $this->booking($business, $product, 1, '2026-10-01', '2026-10-10');
        $this->booking($business, $product, 1, '2026-10-10', '2026-10-20');

        $service = app(AvailabilityService::class);

        $this->assertSame(2, $service->usedUnits($product, '2026-10-09', '2026-10-12'));
    }

    /**
     * Tanggal selesai eksklusif: barang yang kembali pada tanggal 12 sudah
     * bisa disewa lagi pada tanggal 12.
     */
    public function test_tanggal_selesai_eksklusif_tidak_mengunci_hari_pengembalian(): void
    {
        $business = $this->business('gokemping');
        $product = $this->product($business, ['stock' => 2]);

        $this->booking($business, $product, 1, '2026-10-10', '2026-10-12');

        $service = app(AvailabilityService::class);

        $this->assertSame(0, $service->usedUnits($product, '2026-10-12', '2026-10-14'));
        $this->assertSame(2, $service->availableUnits($product, '2026-10-12', '2026-10-14'));
    }

    public function test_booking_menunggu_konfirmasi_ikut_menahan_stok(): void
    {
        $business = $this->business('gokemping');
        $product = $this->product($business, ['stock' => 4]);

        $this->booking(
            $business,
            $product,
            2,
            '2026-10-10',
            '2026-10-13',
            BookingStatus::MenungguKonfirmasi,
        );

        $service = app(AvailabilityService::class);

        $this->assertSame(2, $service->usedUnits($product, '2026-10-10', '2026-10-12'));
        $this->assertSame(2, $service->availableUnits($product, '2026-10-10', '2026-10-12'));
    }

    public function test_booking_dikonfirmasi_dan_sedang_disewa_ikut_menahan_stok(): void
    {
        $business = $this->business('gokemping');
        $product = $this->product($business, ['stock' => 4]);

        $this->booking($business, $product, 1, '2026-10-10', '2026-10-13', BookingStatus::Dikonfirmasi);
        $this->booking($business, $product, 1, '2026-10-10', '2026-10-13', BookingStatus::SedangDisewa);

        $service = app(AvailabilityService::class);

        $this->assertSame(2, $service->usedUnits($product, '2026-10-10', '2026-10-12'));
    }

    public function test_booking_selesai_dan_dibatalkan_tidak_menahan_stok(): void
    {
        $business = $this->business('gokemping');
        $product = $this->product($business, ['stock' => 4]);

        $this->booking($business, $product, 3, '2026-10-10', '2026-10-13', BookingStatus::Selesai);
        $this->booking($business, $product, 3, '2026-10-10', '2026-10-13', BookingStatus::Dibatalkan);

        $service = app(AvailabilityService::class);

        $this->assertSame(0, $service->usedUnits($product, '2026-10-10', '2026-10-12'));
        $this->assertSame(4, $service->availableUnits($product, '2026-10-10', '2026-10-12'));
    }

    public function test_item_booking_produk_lain_tidak_dihitung(): void
    {
        $business = $this->business('gokemping');
        $product = $this->product($business, ['stock' => 2]);
        $other = $this->product($business, ['stock' => 2]);

        $this->booking($business, $product, 1, '2026-10-10', '2026-10-13');
        $this->booking($business, $other, 1, '2026-10-10', '2026-10-13');

        $service = app(AvailabilityService::class);

        $this->assertSame(1, $service->usedUnits($product, '2026-10-10', '2026-10-12'));
    }

    public function test_booking_unit_lain_tidak_dihitung(): void
    {
        $gokemping = $this->business('gokemping');
        $sepeda = $this->business('sewa-sepeda-garut');

        $product = $this->product($gokemping, ['stock' => 2]);

        $foreignBooking = Booking::factory()->forPeriod(
            $sepeda,
            '2026-10-10',
            '2026-10-13',
        )->create();

        BookingItem::factory()->for($foreignBooking)->for($product)->create([
            'quantity' => 2,
        ]);

        $service = app(AvailabilityService::class);

        $this->assertSame(0, $service->usedUnits($product, '2026-10-10', '2026-10-12'));
        $this->assertSame(2, $service->availableUnits($product, '2026-10-10', '2026-10-12'));
    }

    public function test_stok_terpakai_lebih_besar_dari_stok_tidak_menghasilkan_nilai_negatif(): void
    {
        $business = $this->business('gokemping');
        $product = $this->product($business, ['stock' => 1]);

        $this->booking($business, $product, 4, '2026-10-10', '2026-10-13');

        $service = app(AvailabilityService::class);

        $this->assertSame(4, $service->usedUnits($product, '2026-10-10', '2026-10-12'));
        $this->assertSame(0, $service->availableUnits($product, '2026-10-10', '2026-10-12'));
    }

    public function test_admin_unit_lain_tidak_melihat_stok_terpakai_unit_ini(): void
    {
        $gokemping = $this->business('gokemping');
        $sepeda = $this->business('sewa-sepeda-garut');

        $product = $this->product($gokemping, ['stock' => 3]);

        $this->booking($gokemping, $product, 2, '2026-10-10', '2026-10-13');

        $admin = User::factory()->forBusiness($sepeda)->create();

        $this->actingAs($admin);

        $service = app(AvailabilityService::class);

        $this->assertSame(2, $service->usedUnits($product, '2026-10-10', '2026-10-12'));
    }

    public function test_ringkasan_ketersediaan_melaporkan_angka_lengkap(): void
    {
        $business = $this->business('gokemping');
        $product = $this->product($business, ['stock' => 5]);

        $this->booking($business, $product, 3, '2026-10-10', '2026-10-13');

        $summary = app(AvailabilityService::class)->summarise(
            $product,
            '2026-10-10',
            '2026-10-12',
            ['requested' => 3],
        );

        $this->assertSame([
            'product_id' => $product->id,
            'start_date' => '2026-10-10',
            'end_date' => '2026-10-12',
            'stock' => 5,
            'used' => 3,
            'available' => 2,
            'requested' => 3,
            'is_available' => false,
        ], $summary);
    }

    public function test_ringkasan_menandai_cukup_bila_jumlah_terpenuhi(): void
    {
        $business = $this->business('gokemping');
        $product = $this->product($business, ['stock' => 5]);

        $this->booking($business, $product, 3, '2026-10-10', '2026-10-13');

        $summary = app(AvailabilityService::class)->summarise(
            $product,
            '2026-10-10',
            '2026-10-12',
            ['requested' => 2],
        );

        $this->assertTrue($summary['is_available']);
        $this->assertSame(2, $summary['available']);
    }

    public function test_ringkasan_default_jumlah_diminta_satu(): void
    {
        $business = $this->business('gokemping');
        $product = $this->product($business, ['stock' => 5]);

        $summary = app(AvailabilityService::class)->summarise($product, '2026-10-10', '2026-10-12');

        $this->assertSame(1, $summary['requested']);
        $this->assertTrue($summary['is_available']);
    }

    private function business(string $slug): Business
    {
        return Business::factory()->create(['slug' => $slug]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function product(Business $business, array $attributes = []): Product
    {
        return Product::factory()->forBusiness($business)->create($attributes);
    }

    private function booking(
        Business $business,
        Product $product,
        int $quantity,
        string $startDate,
        string $endDate,
        BookingStatus $status = BookingStatus::Dikonfirmasi,
    ): Booking {
        $booking = Booking::factory()->forPeriod(
            $business,
            $startDate,
            $endDate,
            $status,
        )->create();

        BookingItem::factory()->for($booking)->for($product)->create([
            'quantity' => $quantity,
        ]);

        return $booking;
    }
}
