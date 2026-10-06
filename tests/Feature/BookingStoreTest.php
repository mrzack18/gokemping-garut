<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethodType;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Scopes\BusinessScope;
use App\Services\AvailabilityService;
use App\Support\BookingDraft;
use App\Support\BookingReceipt;
use App\Support\TicketToken;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

/**
 * Penyimpanan booking dari draft ke database (ROADMAP 3.11).
 *
 * Test ini mengunci tiga hal yang paling merusak kalau bocor: transaksi harus
 * utuh (tidak ada booking tanpa item atau tanpa pembayaran), harga harus disalin
 * dari tabel produk (BR-09), dan kode booking harus unik per unit per tanggal
 * (BR-10).
 *
 * Stok dan status awal ikut diuji karena keduanya menentukan apakah booking
 * yang tersimpan terbaca admin sesuai yang dijanjikan ke penyewa.
 */
class BookingStoreTest extends TestCase
{
    use RefreshDatabase;

    private Business $camping;

    private Product $tent;

    private Business $bike;

    private Product $bicycle;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->camping = Business::factory()->create([
            'slug' => 'gokemping',
            'booking_code_prefix' => 'GK',
            'is_active' => true,
        ]);

        $this->tent = Product::factory()->forBusiness($this->camping)->create([
            'slug' => 'tenda-4-orang',
            'name' => 'Tenda Dome 4 Person',
            'price' => 50000,
            'price_unit' => 'hari',
            'stock' => 4,
        ]);

        $this->bike = Business::factory()->create([
            'slug' => 'sewa-sepeda-garut',
            'booking_code_prefix' => 'SSG',
            'is_active' => true,
        ]);

        $this->bicycle = Product::factory()->forBusiness($this->bike)->create([
            'slug' => 'sepeda-770',
            'name' => 'Sepeda 770',
            'price' => 25000,
            'price_unit' => 'hari',
            'stock' => 6,
        ]);
    }

    public function test_booking_item_dan_payments_tersimpan_bersama(): void
    {
        $this->cashMethod($this->camping);
        $this->prepareDraft($this->camping, $this->tent, 'cash');

        $this->storeBooking($this->camping)->assertRedirect(route('booking.gokemping.success'));

        $this->assertDatabaseCount('bookings', 1);
        $this->assertDatabaseCount('booking_items', 1);
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('customers', 1);

        $booking = $this->booking();

        $this->assertSame($this->camping->getKey(), $booking->business_id);
        $this->assertSame('2026-10-10', $booking->start_date?->toDateString());
        $this->assertSame('2026-10-12', $booking->end_date?->toDateString());
        $this->assertSame(2, $booking->total_days);
        $this->assertSame(200000, (int) $booking->total);
        $this->assertSame(
            $this->tent->getKey(),
            (int) $this->bookingItem()->product_id,
        );
    }

    public function test_harga_disalin_dari_produk_dan_tidak_ikut_berubah_nanti(): void
    {
        $this->cashMethod($this->camping);
        $this->prepareDraft($this->camping, $this->tent, 'cash');
        $this->storeBooking($this->camping);

        $item = $this->bookingItem();

        $this->assertSame('Tenda Dome 4 Person', $item->product_name);
        $this->assertSame('hari', $item->price_unit);
        $this->assertSame(50000, (int) $item->price);
        $this->assertSame(2, (int) $item->quantity);
        $this->assertSame(200000, (int) $item->subtotal);

        /**
         * BR-09: harga produk berubah setelah transaksi dibuat. Nilai yang
         * tersimpan di `booking_items` harus tetap seperti saat dibooking.
         */
        $this->tent->update(['price' => 75000]);

        $this->assertSame(50000, (int) $this->bookingItem()->fresh()->price);
        $this->assertSame(200000, (int) $this->booking()->fresh()->total);
    }

    public function test_harga_tidak_pernah_diambil_dari_nilai_klien(): void
    {
        $this->cashMethod($this->camping);
        $this->prepareDraft($this->camping, $this->tent, 'cash');

        $this->post($this->storeRoute($this->camping), [
            'subtotal' => 1,
            'total' => 1,
            'price' => 1,
        ])->assertRedirect();

        $this->assertSame(200000, (int) $this->booking()->total);
        $this->assertSame(50000, (int) $this->bookingItem()->price);
    }

    public function test_kode_booking_berurut_per_unit_dan_per_tanggal(): void
    {
        $this->cashMethod($this->camping);
        $this->prepareDraft($this->camping, $this->tent, 'cash');
        $this->storeBooking($this->camping);

        $this->assertMatchesRegularExpression(
            '/^GK-\d{8}-001$/',
            (string) $this->booking()->booking_code,
        );

        $this->prepareDraft($this->camping, $this->tent, 'cash');
        $this->storeBooking($this->camping);

        $this->assertMatchesRegularExpression(
            '/^GK-\d{8}-002$/',
            (string) $this->latestBooking()->booking_code,
        );
    }

    public function test_kode_booking_pakai_prefix_unit_sepeda(): void
    {
        $this->cashMethod($this->bike);
        $this->prepareDraft($this->bike, $this->bicycle, 'cash', [], ['renter_count' => 3]);
        $this->storeBooking($this->bike);

        $booking = $this->latestBooking();

        $this->assertMatchesRegularExpression(
            '/^SSG-\d{8}-001$/',
            (string) $booking->booking_code,
        );
        $this->assertSame(3, $booking->renter_count);
        $this->assertSame(100000, (int) $booking->total);
    }

    public function test_nomor_urut_kode_dihitung_per_tanggal_pembuatan(): void
    {
        $this->cashMethod($this->camping);

        $yesterday = now()->subDay()->format('Ymd');

        $this->existingBookingCode('GK-'.$yesterday.'-001');
        $this->existingBookingCode('GK-'.$yesterday.'-002');

        $this->prepareDraft($this->camping, $this->tent, 'cash');
        $this->storeBooking($this->camping);

        /**
         * Kode memakai tanggal booking dibuat, bukan tanggal mulai sewa, dan
         * nomornya mulai lagi dari satu pada tanggal tersebut.
         */
        $this->assertSame(
            'GK-'.now()->format('Ymd').'-001',
            (string) $this->latestBooking()->booking_code,
        );

        $this->prepareDraft($this->camping, $this->tent, 'cash', [
            'start_date' => '2026-11-10',
            'end_date' => '2026-11-12',
        ]);
        $this->storeBooking($this->camping);

        $codes = $this->campingBookings()->orderBy('id')->pluck('booking_code')->all();

        $this->assertSame('GK-'.now()->format('Ymd').'-001', (string) $codes[2]);
        $this->assertSame('GK-'.now()->format('Ymd').'-002', (string) $codes[3]);
    }

    public function test_status_awal_cash_adalah_belum_dibayar(): void
    {
        $this->cashMethod($this->camping);
        $this->prepareDraft($this->camping, $this->tent, 'cash');
        $this->storeBooking($this->camping);

        $booking = $this->booking();

        $this->assertSame(BookingStatus::MenungguKonfirmasi, $booking->booking_status);
        $this->assertSame(PaymentStatus::BelumDibayar, $booking->payment_status);
        $this->assertSame(PaymentMethodType::Cash, $booking->payment_method);
        $this->assertSame(PaymentStatus::BelumDibayar, $this->payment()->status);
        $this->assertSame(200000, (int) $this->payment()->amount);
        $this->assertNull($this->payment()->proof);
    }

    public function test_status_awal_qris_adalah_menunggu_verifikasi(): void
    {
        PaymentMethod::factory()->forBusiness($this->camping)->qris()->create();
        $this->prepareDraft($this->camping, $this->tent, 'qris');
        $this->uploadProof();
        $this->storeBooking($this->camping);

        $this->assertSame(PaymentStatus::MenungguVerifikasi, $this->booking()->payment_status);
        $this->assertSame(PaymentStatus::MenungguVerifikasi, $this->payment()->status);
        $this->assertSame(PaymentMethodType::Qris, $this->payment()->method);
    }

    public function test_bukti_pembayaran_pindah_ke_payments_dan_berkasnya_tidak_dihapus(): void
    {
        PaymentMethod::factory()->forBusiness($this->camping)->bankTransfer()->create();
        $this->prepareDraft($this->camping, $this->tent, 'bank_transfer');
        $this->uploadProof();
        $this->storeBooking($this->camping);

        $path = $this->payment()->proof;

        $this->assertIsString($path);
        $this->assertStringStartsWith('payments/', $path);

        /**
         * Draft dihapus setelah booking tersimpan, jadi path bukti harus sudah
         * pindah ke kolom `payments.proof`.
         */
        $this->assertNull(session(BookingDraft::SESSION_KEY));
        $this->assertDatabaseHas('payments', ['proof' => $path]);

        /**
         * Berkas sekarang dimiliki record `payments`, jadi draft dihapus
         * tidak boleh ikut menghapusnya dari disk.
         */
        Storage::disk('public')->assertExists($path);
    }

    public function test_qris_tanpa_bukti_dikembalikan_ke_halaman_pembayaran(): void
    {
        PaymentMethod::factory()->forBusiness($this->camping)->qris()->create();
        $this->prepareDraft($this->camping, $this->tent, 'qris');

        $this->storeBooking($this->camping)
            ->assertRedirect(route('booking.gokemping.payment.show', ['method' => 'qris']))
            ->assertSessionHasErrors('proof');

        $this->assertDatabaseCount('bookings', 0);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_draft_hilang_setelah_booking_tersimpan(): void
    {
        $this->cashMethod($this->camping);
        $this->prepareDraft($this->camping, $this->tent, 'cash');
        $this->storeBooking($this->camping);

        $this->assertNull(session(BookingDraft::SESSION_KEY));

        /**
         * Submit ulang tidak boleh membuat booking kedua.
         */
        $this->post($this->storeRoute($this->camping))->assertNotFound();

        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_halaman_sukses_menampilkan_kode_booking(): void
    {
        $this->cashMethod($this->camping);
        $this->prepareDraft($this->camping, $this->tent, 'cash');
        $this->storeBooking($this->camping);

        $code = (string) $this->booking()->booking_code;

        $this->assertSame($code, $this->receipt()['booking_code']);

        $this->get(route('booking.gokemping.success'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('receipt.booking_code', $code)
                ->where('receipt.product_name', 'Tenda Dome 4 Person')
                ->where('receipt.total', 200000)
                ->where('receipt.total_label', '200.000')
                ->where('receipt.period.quantity', 2)
                ->where('receipt.period.duration', 2)
                ->where('receipt.booking_status_label', 'Menunggu Konfirmasi')
                ->where('receipt.payment.method_label', 'Cash')
                ->where('receipt.payment.status_label', 'Belum Dibayar')
            );
    }

    public function test_receipt_membawa_pesan_whatsapp_admin_unit(): void
    {
        $this->camping->update(['name' => 'GoKemping', 'whatsapp' => '6281234567890']);
        $this->cashMethod($this->camping);
        $this->prepareDraft($this->camping, $this->tent, 'cash');
        $this->storeBooking($this->camping);

        $code = (string) $this->booking()->booking_code;
        $whatsapp = $this->receipt()['whatsapp'];

        $this->assertSame('6281234567890', $whatsapp['number']);
        $this->assertSame(
            'https://wa.me/6281234567890?text='.rawurlencode($whatsapp['message']),
            $whatsapp['url'],
        );
        $this->assertStringContainsString('Kode Booking: '.$code, $whatsapp['message']);
        $this->assertStringContainsString('Halo Admin GoKemping,', $whatsapp['message']);

        // QR tiket memakai URL pindai bertanda tangan dari kode booking yang
        // baru saja dibuat.
        $this->assertSame(TicketToken::url($code), $this->receipt()['ticket_url']);

        $this->get(route('booking.gokemping.success'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('receipt.whatsapp.number', '6281234567890')
                ->where('receipt.whatsapp.url', $whatsapp['url'])
            );
    }

    public function test_nomor_admin_tidak_valid_mengosongkan_tautan_tetap_menyimpan_pesan(): void
    {
        $this->camping->update(['whatsapp' => 'belum-diatur']);
        $this->cashMethod($this->camping);
        $this->prepareDraft($this->camping, $this->tent, 'cash');
        $this->storeBooking($this->camping);

        $whatsapp = $this->receipt()['whatsapp'];

        $this->assertNull($whatsapp['number']);
        $this->assertNull($whatsapp['url']);
        $this->assertNotSame('', $whatsapp['message']);

        $this->get(route('booking.gokemping.success'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('receipt.whatsapp.number', null)
                ->where('receipt.whatsapp.url', null)
            );
    }

    public function test_halaman_sukses_unit_lain_mengarah_ke_beranda(): void
    {
        $this->cashMethod($this->camping);
        $this->prepareDraft($this->camping, $this->tent, 'cash');
        $this->storeBooking($this->camping);

        $this->get(route('booking.sewaSepedaGarut.success'))->assertRedirect(route('home'));
    }

    public function test_halaman_sukses_tanpa_receipt_mengarah_ke_beranda(): void
    {
        $this->get(route('booking.gokemping.success'))->assertRedirect(route('home'));
    }

    public function test_stok_terpakai_booking_lain_dikembalikan_ke_review(): void
    {
        $this->cashMethod($this->camping);

        $this->prepareDraft($this->camping, $this->tent, 'cash', ['quantity' => 4]);

        /**
         * Penyewa lain lebih dulu menyelesaikan booking untuk periode yang sama
         * di antara draft dibuat dan tombol konfirmasi ditekan.
         */
        $this->competingBooking($this->camping, $this->tent, '2026-10-10', '2026-10-12', 1);

        $this->storeBooking($this->camping)
            ->assertRedirect(route('booking.gokemping.review'))
            ->assertSessionHasErrors('period');

        $this->assertDatabaseCount('bookings', 1);
        $this->assertDatabaseCount('booking_items', 1);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_booking_satu_hari_menahan_stok_hanya_pada_tanggalnya(): void
    {
        $this->tent->update(['stock' => 2]);
        $this->cashMethod($this->camping);

        $this->prepareDraft($this->camping, $this->tent, 'cash', [
            'start_date' => '2026-10-10',
            'end_date' => '2026-10-10',
            'quantity' => 1,
        ]);

        $this->storeBooking($this->camping)->assertRedirect(route('booking.gokemping.success'));

        $booking = $this->booking();

        $this->assertSame(1, $booking->total_days);
        $this->assertSame(50000, (int) $booking->total);

        $availability = app(AvailabilityService::class);

        /**
         * Periode satu hari harus benar-benar menahan stok pada tanggal itu.
         * Tanpa itu, booking yang sama persis tidak akan pernah beririsan
         * dengan apa pun dan satu barang bisa terjual berkali-kali.
         */
        $this->assertSame(1, $availability->availableUnits($this->tent, '2026-10-10', '2026-10-10'));
        $this->assertSame(2, $availability->availableUnits($this->tent, '2026-10-11', '2026-10-12'));
    }

    public function test_booking_satu_hari_ditolak_oleh_cek_ulang_stok(): void
    {
        $this->tent->update(['stock' => 1]);
        $this->cashMethod($this->camping);

        $this->prepareDraft($this->camping, $this->tent, 'cash', [
            'start_date' => '2026-10-10',
            'end_date' => '2026-10-10',
            'quantity' => 1,
        ]);

        $this->competingBooking($this->camping, $this->tent, '2026-10-10', '2026-10-10', 1);

        $this->storeBooking($this->camping)->assertSessionHasErrors('period');

        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_periode_lain_yang_beririsan_dengan_booking_satu_hari_ditolak(): void
    {
        $this->tent->update(['stock' => 1]);
        $this->cashMethod($this->camping);

        $this->prepareDraft($this->camping, $this->tent, 'cash', [
            'start_date' => '2026-10-09',
            'end_date' => '2026-10-11',
            'quantity' => 1,
        ]);

        $this->competingBooking($this->camping, $this->tent, '2026-10-10', '2026-10-10', 1);

        $this->storeBooking($this->camping)->assertSessionHasErrors('period');

        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_hari_berikutnya_tidak_beririsan_dengan_booking_satu_hari(): void
    {
        $this->tent->update(['stock' => 1]);
        $this->cashMethod($this->camping);

        $this->competingBooking($this->camping, $this->tent, '2026-10-10', '2026-10-10', 1);

        $this->prepareDraft($this->camping, $this->tent, 'cash', [
            'start_date' => '2026-10-11',
            'end_date' => '2026-10-12',
            'quantity' => 1,
        ]);

        $this->storeBooking($this->camping)->assertRedirect(route('booking.gokemping.success'));

        $this->assertDatabaseCount('bookings', 2);
    }

    public function test_draft_unit_lain_tidak_bisa_disimpan(): void
    {
        $this->cashMethod($this->camping);
        $this->prepareDraft($this->camping, $this->tent, 'cash');

        $this->post(route('booking.sewaSepedaGarut.store'))->assertNotFound();

        $this->assertDatabaseCount('bookings', 0);
        $this->assertDatabaseCount('customers', 0);
    }

    public function test_penyewa_dengan_whatsapp_sama_tidak_terpecah(): void
    {
        $this->cashMethod($this->camping);

        $this->prepareDraft($this->camping, $this->tent, 'cash');
        $this->storeBooking($this->camping);

        $this->prepareDraft($this->camping, $this->tent, 'cash', [
            'start_date' => '2026-10-20',
            'end_date' => '2026-10-22',
        ], ['name' => 'Zaki Update']);
        $this->storeBooking($this->camping);

        $this->assertDatabaseCount('customers', 1);
        $this->assertDatabaseCount('bookings', 2);
        $this->assertSame(
            '628123456789',
            (string) Customer::query()->firstOrFail()->whatsapp,
        );
        $this->assertSame('Zaki Update', (string) Customer::query()->firstOrFail()->name);
    }

    public function test_jumlah_penyewa_hanya_disimpan_untuk_unit_sepeda(): void
    {
        $this->cashMethod($this->camping);
        $this->prepareDraft($this->camping, $this->tent, 'cash', [], ['renter_count' => 4]);
        $this->storeBooking($this->camping);

        $this->assertNull($this->booking()->renter_count);
    }

    public function test_transaksi_dibatalkan_penuh_saat_item_gagal(): void
    {
        $this->cashMethod($this->camping);
        $this->prepareDraft($this->camping, $this->tent, 'cash');

        $event = 'eloquent.creating: '.BookingItem::class;

        Event::listen($event, function (): void {
            throw new RuntimeException('kegagalan buatan pada item booking');
        });

        try {
            $this->post($this->storeRoute($this->camping));
        } catch (RuntimeException) {
            // Kegagalan yang diharapkan.
        } finally {
            Event::forget($event);
        }

        /**
         * Tidak boleh ada booking, item, pembayaran, maupun penyewa yang
         * tersisa dari transaksi yang gagal di tengah jalan.
         */
        $this->assertDatabaseCount('bookings', 0);
        $this->assertDatabaseCount('booking_items', 0);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('customers', 0);
    }

    public function test_metode_yang_sudah_tidak_aktif_mengarah_kembali_ke_review(): void
    {
        $method = $this->cashMethod($this->camping);
        $this->prepareDraft($this->camping, $this->tent, 'cash');

        $method->update(['is_active' => false]);

        $this->storeBooking($this->camping)->assertRedirect(route('booking.gokemping.review'));

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_tanpa_metode_di_draft_mengarah_kembali_ke_review(): void
    {
        $this->cashMethod($this->camping);
        $this->createPeriodDraft($this->camping, $this->tent);
        $this->saveCustomer($this->camping);

        $this->storeBooking($this->camping)->assertRedirect(route('booking.gokemping.review'));

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_biodata_tidak_lengkap_mengarah_kembali_ke_biodata(): void
    {
        $this->cashMethod($this->camping);
        $this->createPeriodDraft($this->camping, $this->tent);

        $this->storeBooking($this->camping)->assertRedirect(route('booking.gokemping.biodata'));

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_tanpa_draft_menghasilkan_not_found(): void
    {
        $this->cashMethod($this->camping);

        $this->post($this->storeRoute($this->camping))->assertNotFound();
    }

    private function cashMethod(Business $business): PaymentMethod
    {
        return PaymentMethod::factory()->forBusiness($business)->cash()->create();
    }

    /**
     * Draft lengkap sampai metode pembayaran dipilih, termasuk bukti bila
     * metodenya mewajibkan.
     *
     * @param  array<string, mixed>  $period
     * @param  array<string, mixed>  $customer
     */
    private function prepareDraft(
        Business $business,
        Product $product,
        string $method,
        array $period = [],
        array $customer = [],
    ): void {
        $this->createPeriodDraft($business, $product, $period);
        $this->saveCustomer($business, $customer);

        $this->post(route('booking.'.$this->prefix($business).'.payment.store'), [
            'method' => $method,
        ])->assertRedirect();
    }

    /**
     * @param  array<string, mixed>  $period
     */
    private function createPeriodDraft(Business $business, Product $product, array $period = []): void
    {
        $this->post(
            route('booking.'.$this->prefix($business).'.draft.store', ['product' => $product->slug]),
            array_merge([
                'start_date' => '2026-10-10',
                'end_date' => '2026-10-12',
                'quantity' => 2,
            ], $period),
        )->assertSessionHasNoErrors();
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function saveCustomer(Business $business, array $overrides = []): void
    {
        $this->post(route('booking.'.$this->prefix($business).'.biodata.store'), array_merge([
            'name' => 'Zaki',
            'whatsapp' => '08123456789',
            'nik' => '3201234567890001',
            'address' => 'Garut',
            'city' => 'Garut',
        ], $overrides))->assertSessionHasNoErrors();
    }

    private function uploadProof(): void
    {
        $this->post(
            route('booking.'.$this->prefix($this->camping).'.payment.proof.store'),
            ['proof' => UploadedFile::fake()->image('bukti.jpg')],
        )->assertRedirect();
    }

    private function storeBooking(Business $business)
    {
        return $this->post($this->storeRoute($business));
    }

    /**
     * Booking dari penyewa lain yang dibuat langsung di database, untuk
     * mensimulasikan stok yang terpakai di antara draft dibuat dan booking
     * disimpan.
     */
    private function competingBooking(
        Business $business,
        Product $product,
        string $startDate,
        string $endDate,
        int $quantity,
    ): void {
        $booking = Booking::factory()
            ->forPeriod($business, $startDate, $endDate)
            ->create(['booking_code' => 'GK-COMPETING-'.fake()->unique()->numerify('###')]);

        BookingItem::factory()->create([
            'booking_id' => $booking->getKey(),
            'product_id' => $product->getKey(),
            'quantity' => $quantity,
        ]);
    }

    /**
     * Booking lama dengan kode tertentu, untuk menguji bahwa nomor urut kode
     * dihitung ulang dari kode yang benar-benar tersimpan.
     */
    private function existingBookingCode(string $code): void
    {
        Booking::factory()->forPeriod(
            $this->camping,
            now()->subMonth()->toDateString(),
            now()->subMonth()->addDay()->toDateString(),
        )->create(['booking_code' => $code]);
    }

    private function storeRoute(Business $business): string
    {
        return route('booking.'.$this->prefix($business).'.store');
    }

    private function prefix(Business $business): string
    {
        return $business->slug === 'gokemping' ? 'gokemping' : 'sewaSepedaGarut';
    }

    private function booking(): Booking
    {
        return $this->bookingQuery()->firstOrFail();
    }

    private function latestBooking(): Booking
    {
        return $this->bookingQuery()->orderByDesc('id')->firstOrFail();
    }

    /**
     * @return Builder<Booking>
     */
    private function bookingQuery(): Builder
    {
        return BusinessScope::withoutBusinessScope(Booking::query());
    }

    /**
     * @return Builder<Booking>
     */
    private function campingBookings(): Builder
    {
        return $this->bookingQuery()->where('business_id', $this->camping->getKey());
    }

    private function bookingItem(): BookingItem
    {
        return BusinessScope::withoutBusinessScope(BookingItem::query())->firstOrFail();
    }

    private function payment(): Payment
    {
        return BusinessScope::withoutBusinessScope(Payment::query())->firstOrFail();
    }

    /**
     * @return array<string, mixed>
     */
    private function receipt(): array
    {
        $receipt = session(BookingReceipt::SESSION_KEY);

        $this->assertIsArray($receipt);

        return $receipt;
    }
}
