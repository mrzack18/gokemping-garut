<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethodType;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Payment;
use App\Support\BookingWhatsappMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * BR-06: nomor WhatsApp tujuan mengikuti unit bisnis.
 *
 * PRD section 19: pesan otomatis berisi detail booking, data penyewa, dan
 * metode pembayaran, lalu dibuka lewat `wa.me`.
 */
class BookingWhatsappMessageTest extends TestCase
{
    use RefreshDatabase;

    private Business $camping;

    private Business $bicycle;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->camping = Business::factory()->create([
            'name' => 'GoKemping',
            'slug' => 'gokemping',
            'whatsapp' => '6281234567890',
        ]);

        $this->bicycle = Business::factory()->create([
            'name' => 'Sewa Sepeda Garut',
            'slug' => 'sewa-sepeda-garut',
            'whatsapp' => '629876543210',
        ]);

        /**
         * Satu penyewa dipakai ulang di dalam satu test, karena
         * `customers.whatsapp` unik dan pesan WhatsApp_admin ke admin tetap
         * sama walau orang yang menyewa booking kedua.
         */
        $this->customer = Customer::factory()->create([
            'name' => 'Zaki Muhammad',
            'whatsapp' => '628123456789',
            'nik' => '3207123456780001',
            'address' => 'Garut',
            'city' => 'Jawa Barat',
        ]);
    }

    #[Test]
    public function pesan_memuat_detail_booking_sesuai_template_prd(): void
    {
        $booking = $this->booking($this->camping, [
            'booking_code' => 'GK-20261003-001',
            'start_date' => '2026-10-10',
            'end_date' => '2026-10-12',
            'total_days' => 2,
            'total' => 300000,
        ], quantity: 2);

        $message = BookingWhatsappMessage::build($booking, $this->camping);

        $this->assertStringStartsWith('Halo Admin GoKemping,', $message);
        $this->assertStringContainsString('Saya ingin melakukan booking sewa.', $message);
        $this->assertStringContainsString('DETAIL BOOKING', $message);
        $this->assertStringContainsString('Kode Booking: GK-20261003-001', $message);
        $this->assertStringContainsString('Tenda Dome 4 Person (2 Unit)', $message);
        $this->assertStringContainsString('Jumlah:'."\n".'2 Unit', $message);
        $this->assertStringContainsString('10 Oktober 2026 - 12 Oktober 2026', $message);
        $this->assertStringContainsString('2 Hari', $message);
        $this->assertStringContainsString('Rp300.000', $message);
        $this->assertStringContainsString('Mohon konfirmasi booking saya.', $message);
        $this->assertStringEndsWith('Terima kasih.', $message);
    }

    #[Test]
    public function pesan_memuat_data_penyewa_lengkap(): void
    {
        $booking = $this->booking($this->camping);

        $message = BookingWhatsappMessage::build($booking, $this->camping);

        $this->assertStringContainsString('DATA PENYEWA', $message);
        $this->assertStringContainsString('Nama:'."\n".'Zaki Muhammad', $message);
        $this->assertStringContainsString('No. WhatsApp:'."\n".'628123456789', $message);
        $this->assertStringContainsString('Garut, Jawa Barat', $message);
    }

    #[Test]
    public function nik_penyewa_tidak_diteruskan_lengkap_ke_pesan(): void
    {
        $booking = $this->booking($this->camping, [], nik: '3207123456780001');

        $message = BookingWhatsappMessage::build($booking, $this->camping);

        $this->assertStringContainsString('3207••••••••0001', $message);
        $this->assertStringNotContainsString('3207123456780001', $message);
    }

    #[Test]
    public function nik_yang_terlalu_pendik_disamar_sepenuhnya(): void
    {
        $booking = $this->booking($this->camping, [], nik: '99');

        $message = BookingWhatsappMessage::build($booking, $this->camping);

        $this->assertStringContainsString('NIK:'."\n".str_repeat('•', 8), $message);
        $this->assertStringNotContainsString('99'."\n", $message);
    }

    #[Test]
    public function jumlah_penyewa_hanya_ada_di_unit_sewa_sepeda(): void
    {
        $withRenter = $this->booking($this->bicycle, [
            'booking_code' => 'SSG-20261003-001',
        ], renterCount: 3);

        $withoutRenter = $this->booking($this->bicycle, [
            'booking_code' => 'SSG-20261003-002',
        ]);

        $this->assertStringContainsString(
            'Jumlah Penyewa:'."\n".'3 orang',
            BookingWhatsappMessage::build($withRenter, $this->bicycle),
        );

        $this->assertStringNotContainsString(
            'Jumlah Penyewa:',
            BookingWhatsappMessage::build($withoutRenter, $this->bicycle),
        );
    }

    #[Test]
    public function catatan_penyewa_ikut_dibawa(): void
    {
        $booking = $this->booking($this->camping, [], notes: 'Bawa tenda tambahan');

        $message = BookingWhatsappMessage::build($booking, $this->camping);

        $this->assertStringContainsString('Catatan Penyewa:'."\n".'Bawa tenda tambahan', $message);
    }

    #[Test]
    public function catatan_kosong_tidak_membuat_baris_kosong(): void
    {
        $booking = $this->booking($this->camping, [], notes: '   ');

        $message = BookingWhatsappMessage::build($booking, $this->camping);

        $this->assertStringNotContainsString('Catatan Penyewa:', $message);
    }

    #[Test]
    public function metode_dan_status_bukti_payments_disertakan(): void
    {
        $qris = $this->booking($this->camping, [
            'booking_code' => 'GK-20261003-002',
        ], method: PaymentMethodType::Qris, proof: 'qris/2026/abc.jpg');

        $this->assertStringContainsString(
            'METODE PEMBAYARAN',
            BookingWhatsappMessage::build($qris, $this->camping),
        );
        $this->assertStringContainsString(
            'Sudah diupload',
            BookingWhatsappMessage::build($qris, $this->camping),
        );

        $cash = $this->booking($this->camping, [
            'booking_code' => 'GK-20261003-003',
        ], method: PaymentMethodType::Cash);

        $message = BookingWhatsappMessage::build($cash, $this->camping);

        $this->assertStringContainsString('Tidak diperlukan (Cash)', $message);
        $this->assertStringNotContainsString('Sudah diupload', $message);
    }

    #[Test]
    public function bukti_yang_wajib_tapi_belum_ada_disebut_sebagai_belum_diupload(): void
    {
        $booking = $this->booking($this->camping, [
            'booking_code' => 'GK-20261003-004',
        ], method: PaymentMethodType::BankTransfer);

        $message = BookingWhatsappMessage::build($booking, $this->camping);

        $this->assertStringContainsString('Belum diupload', $message);
    }

    #[Test]
    public function tautan_wa_me_mengikuti_nomor_whatsapp_unit(): void
    {
        $message = 'Halo Admin';

        $this->assertSame(
            'https://wa.me/6281234567890?text='.rawurlencode($message),
            BookingWhatsappMessage::link($this->camping, $message),
        );

        $this->assertSame(
            'https://wa.me/629876543210?text='.rawurlencode($message),
            BookingWhatsappMessage::link($this->bicycle, $message),
        );
    }

    #[Test]
    public function pesan_berbaris_banyak_tetap_ter_enter_encode(): void
    {
        $link = BookingWhatsappMessage::link($this->camping, "baris satu\nbaris dua");

        $this->assertNotFalse($link);
        $this->assertStringNotContainsString("\n", $link);
        $this->assertStringContainsString('%0A', $link);
    }

    #[Test]
    public function nomor_whats_app_berformat_aneh_tetap_dinormalkan(): void
    {
        $business = Business::factory()->create(['whatsapp' => '+62 812-9999-0000']);

        $this->assertSame('6281299990000', BookingWhatsappMessage::number($business));
    }

    #[Test]
    public function nomor_admin_yang_tidak_valid_membuat_tautan_dikosongkan(): void
    {
        $business = Business::factory()->create(['whatsapp' => 'bukan-nomor']);

        $this->assertNull(BookingWhatsappMessage::number($business));
        $this->assertNull(BookingWhatsappMessage::link($business, 'Halo Admin'));
    }

    #[Test]
    public function produk_yang_belum_ada_tidak_membuat_pesan_kosong(): void
    {
        $booking = Booking::factory()->create([
            'business_id' => $this->camping->getKey(),
            'booking_code' => 'GK-20261003-009',
        ]);

        $message = BookingWhatsappMessage::build($booking, $this->camping);

        $this->assertStringContainsString('Produk:'."\n".'-'."\n", $message);
        $this->assertStringContainsString('Jumlah:'."\n".'0 Unit', $message);
    }

    /**
     * Booking lengkap dengan item, customer, dan payment.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function booking(
        Business $business,
        array $attributes = [],
        int $quantity = 1,
        ?string $nik = null,
        ?PaymentMethodType $method = null,
        ?string $proof = null,
        ?int $renterCount = null,
        ?string $notes = null,
    ): Booking {
        $method ??= PaymentMethodType::Cash;

        if ($nik !== null) {
            $this->customer->update(['nik' => $nik]);
        }

        $booking = Booking::factory()->create([
            ...$attributes,
            'business_id' => $business->getKey(),
            'customer_id' => $this->customer->getKey(),
            'payment_method' => $method,
            'booking_status' => BookingStatus::MenungguKonfirmasi,
            'renter_count' => $renterCount,
            'notes' => $notes,
        ]);

        BookingItem::factory()->create([
            'booking_id' => $booking->getKey(),
            'quantity' => $quantity,
            'product_name' => 'Tenda Dome 4 Person',
            'subtotal' => 300000,
        ]);

        Payment::factory()->create([
            'booking_id' => $booking->getKey(),
            'business_id' => $business->getKey(),
            'method' => $method,
            'proof' => $proof,
        ]);

        return $booking;
    }
}
