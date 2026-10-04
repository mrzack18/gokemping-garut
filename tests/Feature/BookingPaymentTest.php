<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethodType;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Business;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Support\BookingDraft;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Halaman pembayaran dan pemilihan metode (ROADMAP 3.9).
 *
 * Fokus test ini adalah aturan yang tidak boleh bocor dari klien: metode harus
 * milik unit bisnis yang diakses, harus aktif, dan harus punya data pembayaran
 * yang cukup sebelum penyewa boleh mencapai halaman pembayaran.
 */
class BookingPaymentTest extends TestCase
{
    use RefreshDatabase;

    private Business $camping;

    private Product $tent;

    private Business $bike;

    private Product $bicycle;

    protected function setUp(): void
    {
        parent::setUp();

        $this->camping = Business::factory()->create([
            'slug' => 'gokemping',
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

    public function test_halaman_pembayaran_cash_tampil_dengan_total(): void
    {
        $this->cashMethod($this->camping, 'Bayar di pos kemah.');
        $this->createCompleteDraft($this->camping, $this->tent);
        $this->selectMethod($this->camping, 'cash');

        $this->get($this->paymentRoute($this->camping, 'cash'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('booking/payment')
                ->where('method.type', 'cash')
                ->where('method.label', 'Cash')
                ->where('method.instructions', 'Bayar di pos kemah.')
                ->where('method.requires_proof', false)
                ->where('product.name', 'Tenda Dome 4 Person')
                ->where('period.duration_label', '2 hari')
                ->where('availability.is_available', true)
                ->where('pricing.total', 200000)
            );
    }

    public function test_halaman_pembayaran_transfer_menampilkan_data_rekening(): void
    {
        PaymentMethod::factory()->forBusiness($this->camping)->bankTransfer(
            'BCA',
            '1234567890',
            'PT.GoKemping',
        )->create();

        $this->createCompleteDraft($this->camping, $this->tent);
        $this->selectMethod($this->camping, 'bank_transfer');

        $this->get($this->paymentRoute($this->camping, 'bank_transfer'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('method.bank_name', 'BCA')
                ->where('method.account_number', '1234567890')
                ->where('method.account_name', 'PT.GoKemping')
                ->where('method.requires_proof', true)
            );
    }

    public function test_halaman_pembayaran_qris_menampilkan_gambar_dan_merchant(): void
    {
        PaymentMethod::factory()->forBusiness($this->camping)->qris('GoKemping Garut')->create();

        $this->createCompleteDraft($this->camping, $this->tent);
        $this->selectMethod($this->camping, 'qris');

        $this->get($this->paymentRoute($this->camping, 'qris'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('method.merchant_name', 'GoKemping Garut')
                ->where('method.requires_proof', true)
                ->has('method.qris_image_url')
            );
    }

    public function test_total_pembayaran_mengikuti_durasi_dan_jumlah(): void
    {
        $this->cashMethod($this->camping);
        $this->createPeriodDraft($this->camping, $this->tent, [
            'start_date' => '2026-10-10',
            'end_date' => '2026-10-14',
            'quantity' => 3,
        ]);
        $this->saveCustomer($this->camping);
        $this->selectMethod($this->camping, 'cash');

        // 50.000 x 3 unit x 4 hari = 600.000
        $this->get($this->paymentRoute($this->camping, 'cash'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('pricing.total', 600000));
    }

    public function test_harga_diambil_dari_produk_terkini(): void
    {
        $this->cashMethod($this->camping);
        $this->createCompleteDraft($this->camping, $this->tent);

        $this->tent->update(['price' => 75000]);

        $this->selectMethod($this->camping, 'cash');

        $this->get($this->paymentRoute($this->camping, 'cash'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('pricing.total', 300000));
    }

    public function test_ketersediaan_berubah_ditandai_tidak_tersedia(): void
    {
        $this->cashMethod($this->camping);
        $this->createCompleteDraft($this->camping, $this->tent);
        $this->selectMethod($this->camping, 'cash');

        $booking = Booking::factory()->forPeriod(
            $this->camping,
            '2026-10-10',
            '2026-10-12',
            BookingStatus::Dikonfirmasi,
        )->create();

        BookingItem::factory()->for($booking)->for($this->tent)->create(['quantity' => 3]);

        $this->get($this->paymentRoute($this->camping, 'cash'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('availability.available', 1)
                ->where('availability.requested', 2)
                ->where('availability.is_available', false)
            );
    }

    public function test_memilih_metode_disimpan_ke_draft(): void
    {
        PaymentMethod::factory()->forBusiness($this->camping)->bankTransfer()->create();
        $this->createCompleteDraft($this->camping, $this->tent);

        $this->selectMethod($this->camping, 'bank_transfer')
            ->assertRedirect($this->paymentRoute($this->camping, 'bank_transfer'));

        $this->assertSame('bank_transfer', session(BookingDraft::SESSION_KEY)['payment_method']);
    }

    public function test_memilih_metode_tidak_menulis_ke_database(): void
    {
        $this->cashMethod($this->camping);
        $this->createCompleteDraft($this->camping, $this->tent);

        $this->selectMethod($this->camping, 'cash');

        $this->assertDatabaseCount('bookings', 0);
        $this->assertDatabaseCount('booking_items', 0);
        $this->assertDatabaseCount('customers', 0);
    }

    public function test_metode_tidak_dikenal_ditolak(): void
    {
        $this->cashMethod($this->camping);
        $this->createCompleteDraft($this->camping, $this->tent);

        $this->selectMethod($this->camping, 'bitcoin')
            ->assertSessionHasErrors('method');

        $this->assertArrayNotHasKey('payment_method', session(BookingDraft::SESSION_KEY));
    }

    public function test_metode_kosong_ditolak(): void
    {
        $this->cashMethod($this->camping);
        $this->createCompleteDraft($this->camping, $this->tent);

        $this->selectMethod($this->camping, '')
            ->assertSessionHasErrors('method');
    }

    public function test_metode_nonaktif_ditolak(): void
    {
        $this->cashMethod($this->camping, isActive: false);
        $this->createCompleteDraft($this->camping, $this->tent);

        $this->selectMethod($this->camping, 'cash')
            ->assertSessionHasErrors('method');

        $this->assertArrayNotHasKey('payment_method', session(BookingDraft::SESSION_KEY));
    }

    public function test_metode_milik_unit_lain_ditolak(): void
    {
        $this->cashMethod($this->bike);
        $this->createCompleteDraft($this->camping, $this->tent);

        $this->selectMethod($this->camping, 'cash')
            ->assertSessionHasErrors('method');

        $this->assertArrayNotHasKey('payment_method', session(BookingDraft::SESSION_KEY));
    }

    public function test_qris_tanpa_gambar_ditolak(): void
    {
        PaymentMethod::factory()->forBusiness($this->camping)->state([
            'type' => PaymentMethodType::Qris,
            'merchant_name' => 'GoKemping',
            'qris_image' => null,
        ])->create();

        $this->createCompleteDraft($this->camping, $this->tent);

        $this->selectMethod($this->camping, 'qris')
            ->assertSessionHasErrors('method');
    }

    public function test_transfer_tanpa_nomor_rekening_ditolak(): void
    {
        PaymentMethod::factory()->forBusiness($this->camping)->state([
            'type' => PaymentMethodType::BankTransfer,
            'bank_name' => 'BCA',
            'account_number' => null,
            'account_name' => 'PT.GoKemping',
        ])->create();

        $this->createCompleteDraft($this->camping, $this->tent);

        $this->selectMethod($this->camping, 'bank_transfer')
            ->assertSessionHasErrors('method');
    }

    public function test_stok_berkurang_diarahkan_kembali_ke_review(): void
    {
        $this->cashMethod($this->camping);
        $this->createCompleteDraft($this->camping, $this->tent);

        $booking = Booking::factory()->forPeriod(
            $this->camping,
            '2026-10-10',
            '2026-10-12',
            BookingStatus::Dikonfirmasi,
        )->create();

        BookingItem::factory()->for($booking)->for($this->tent)->create(['quantity' => 4]);

        $this->selectMethod($this->camping, 'cash')
            ->assertRedirect(route('booking.gokemping.review'));

        $this->assertArrayNotHasKey('payment_method', session(BookingDraft::SESSION_KEY));
    }

    public function test_halaman_pembayaran_tanpa_draft_menghasilkan_404(): void
    {
        $this->cashMethod($this->camping);

        $this->get($this->paymentRoute($this->camping, 'cash'))->assertNotFound();
    }

    public function test_halaman_pembayaran_tanpa_metode_diarahkan_ke_review(): void
    {
        $this->cashMethod($this->camping);
        $this->createCompleteDraft($this->camping, $this->tent);

        $this->get($this->paymentRoute($this->camping, 'cash'))
            ->assertRedirect(route('booking.gokemping.review'));
    }

    public function test_halaman_pembayaran_metode_berbeda_diarahkan_ke_review(): void
    {
        $this->cashMethod($this->camping);
        $this->createCompleteDraft($this->camping, $this->tent);
        $this->selectMethod($this->camping, 'cash');

        $this->get($this->paymentRoute($this->camping, 'bank_transfer'))
            ->assertRedirect(route('booking.gokemping.review'));
    }

    public function test_metode_jadi_nonaktif_setelah_dipilih_diarahkan_ke_review(): void
    {
        $method = $this->cashMethod($this->camping);
        $this->createCompleteDraft($this->camping, $this->tent);
        $this->selectMethod($this->camping, 'cash');

        $method->update(['is_active' => false]);

        $this->get($this->paymentRoute($this->camping, 'cash'))
            ->assertRedirect(route('booking.gokemping.review'));
    }

    public function test_metode_kurang_lengkap_setelah_dipilih_diarahkan_ke_review(): void
    {
        $method = PaymentMethod::factory()->forBusiness($this->camping)->qris()->create();
        $this->createCompleteDraft($this->camping, $this->tent);
        $this->selectMethod($this->camping, 'qris');

        $method->update(['qris_image' => null]);

        $this->get($this->paymentRoute($this->camping, 'qris'))
            ->assertRedirect(route('booking.gokemping.review'));
    }

    public function test_halaman_pembayaran_melewati_biodata_diarahkan_ke_form_biodata(): void
    {
        $this->cashMethod($this->camping);
        $this->createPeriodDraft($this->camping, $this->tent);
        $this->selectMethod($this->camping, 'cash');

        $this->get($this->paymentRoute($this->camping, 'cash'))
            ->assertRedirect(route('booking.gokemping.biodata'));
    }

    public function test_draft_unit_lain_menghasilkan_404(): void
    {
        $this->cashMethod($this->bike);
        $this->createCompleteDraft($this->camping, $this->tent);
        $this->selectMethod($this->camping, 'cash');

        $this->get($this->paymentRoute($this->bike, 'cash'))->assertNotFound();
    }

    public function test_unit_sepeda_menampilkan_harga_sepedanya(): void
    {
        $this->cashMethod($this->bike);
        $this->createPeriodDraft($this->bike, $this->bicycle, ['quantity' => 3]);
        $this->saveCustomer($this->bike, ['renter_count' => 3]);
        $this->selectMethod($this->bike, 'cash');

        // 25.000 x 3 unit x 2 hari = 150.000
        $this->get($this->paymentRoute($this->bike, 'cash'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('product.name', 'Sepeda 770')
                ->where('pricing.total', 150000)
            );
    }

    public function test_alur_lengkap_dari_review_ke_pembayaran(): void
    {
        $this->cashMethod($this->camping);

        $this->get(route('booking.gokemping.create', ['product' => $this->tent->slug]))
            ->assertOk();

        $this->createPeriodDraft($this->camping, $this->tent);
        $this->saveCustomer($this->camping);

        $this->get(route('booking.gokemping.review'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('pricing.total', 200000));

        $this->selectMethod($this->camping, 'cash')
            ->assertRedirect($this->paymentRoute($this->camping, 'cash'));

        $this->get($this->paymentRoute($this->camping, 'cash'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('method.type', 'cash'));
    }

    public function test_halaman_pembayaran_tidak_membutuhkan_login(): void
    {
        $this->cashMethod($this->camping);
        $this->createCompleteDraft($this->camping, $this->tent);
        $this->selectMethod($this->camping, 'cash');

        $this->assertGuest();

        $this->get($this->paymentRoute($this->camping, 'cash'))->assertOk();
    }

    private function createCompleteDraft(
        Business $business,
        Product $product,
        array $customer = [],
    ): void {
        $this->createPeriodDraft($business, $product);
        $this->saveCustomer($business, $customer);
    }

    private function createPeriodDraft(
        Business $business,
        Product $product,
        array $period = [],
    ): void {
        $this->post($this->draftRoute($business, $product), array_merge([
            'start_date' => '2026-10-10',
            'end_date' => '2026-10-12',
            'quantity' => 2,
        ], $period))->assertRedirect();
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
            'email' => 'zaki@example.com',
            'notes' => 'Datang pagi',
        ], $overrides))->assertRedirect();
    }

    private function cashMethod(
        Business $business,
        string $instructions = 'Pembayaran dilakukan langsung di lokasi.',
        bool $isActive = true,
    ): PaymentMethod {
        return PaymentMethod::factory()->forBusiness($business)->cash($instructions)->create([
            'is_active' => $isActive,
        ]);
    }

    private function selectMethod(Business $business, string $method)
    {
        return $this->post(
            route('booking.'.$this->prefix($business).'.payment.store'),
            ['method' => $method],
        );
    }

    private function prefix(Business $business): string
    {
        return $business->slug === 'gokemping' ? 'gokemping' : 'sewaSepedaGarut';
    }

    private function draftRoute(Business $business, Product $product): string
    {
        return route('booking.'.$this->prefix($business).'.draft.store', ['product' => $product->slug]);
    }

    private function paymentRoute(Business $business, string $method): string
    {
        return route('booking.'.$this->prefix($business).'.payment.show', ['method' => $method]);
    }
}
