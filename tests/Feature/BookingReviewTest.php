<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Business;
use App\Models\Product;
use App\Support\BookingDraft;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingReviewTest extends TestCase
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
            'price' => 25000,
            'price_unit' => 'hari',
            'stock' => 6,
        ]);
    }

    /**
     * Membuat draft lengkap: produk, periode, dan biodata penyewa.
     */
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

    private function prefix(Business $business): string
    {
        return $business->slug === 'gokemping' ? 'gokemping' : 'sewaSepedaGarut';
    }

    private function draftRoute(Business $business, Product $product): string
    {
        return route('booking.'.$this->prefix($business).'.draft.store', ['product' => $product->slug]);
    }

    private function reviewRoute(Business $business): string
    {
        return route('booking.'.$this->prefix($business).'.review');
    }

    public function test_review_tampil_dengan_ringkasan_lengkap(): void
    {
        $this->createCompleteDraft($this->camping, $this->tent);

        $this->get($this->reviewRoute($this->camping))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('booking/review')
                ->where('product.name', 'Tenda Dome 4 Person')
                ->where('period.quantity', 2)
                ->where('period.duration', 2)
                ->where('period.duration_label', '2 hari')
                ->where('period.start_date_label', '10 Oktober 2026')
                ->where('period.end_date_label', '12 Oktober 2026')
                ->where('pricing.price', 50000)
                ->where('pricing.subtotal', 200000)
                ->where('pricing.total', 200000)
                ->where('customer.name', 'Zaki')
                ->where('customer.whatsapp', '628123456789')
                ->where('customer.nik', '3201234567890001')
                ->where('customer.address', 'Garut')
                ->where('isBikeRental', false)
            );
    }

    public function test_total_mengikuti_durasi_dan_jumlah(): void
    {
        $this->createPeriodDraft($this->camping, $this->tent, [
            'start_date' => '2026-10-10',
            'end_date' => '2026-10-14',
            'quantity' => 3,
        ]);
        $this->saveCustomer($this->camping);

        // 50.000 x 3 unit x 4 hari (10 sampai 14 Oktober) = 600.000
        $this->get($this->reviewRoute($this->camping))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('period.duration', 4)
                ->where('pricing.subtotal', 600000)
                ->where('pricing.total', 600000)
            );
    }

    public function test_periode_satu_hari_berdurasi_satu_hari(): void
    {
        $this->createPeriodDraft($this->camping, $this->tent, [
            'start_date' => '2026-10-10',
            'end_date' => '2026-10-10',
            'quantity' => 1,
        ]);
        $this->saveCustomer($this->camping);

        $this->get($this->reviewRoute($this->camping))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('period.duration', 1)
                ->where('period.duration_label', '1 hari')
                ->where('pricing.total', 50000)
            );
    }

    public function test_harga_diambil_dari_produk_terkini(): void
    {
        $this->createCompleteDraft($this->camping, $this->tent);

        $this->tent->update(['price' => 75000]);

        $this->get($this->reviewRoute($this->camping))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('pricing.price', 75000)
                ->where('pricing.total', 300000)
            );
    }

    public function test_harga_label_berbahasa_indonesia(): void
    {
        $this->createCompleteDraft($this->camping, $this->tent);

        $this->get($this->reviewRoute($this->camping))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('pricing.price_label', '50.000'));
    }

    public function test_ketersediaan_ditampilkan_dan_cukup(): void
    {
        $this->createCompleteDraft($this->camping, $this->tent);

        $this->get($this->reviewRoute($this->camping))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('availability.available', 4)
                ->where('availability.requested', 2)
                ->where('availability.is_available', true)
            );
    }

    public function test_ketersediaan_berubah_ditandai_tidak_tersedia(): void
    {
        $this->createCompleteDraft($this->camping, $this->tent);

        $booking = Booking::factory()->forPeriod(
            $this->camping,
            '2026-10-10',
            '2026-10-12',
            BookingStatus::Dikonfirmasi,
        )->create();

        BookingItem::factory()->for($booking)->for($this->tent)->create(['quantity' => 3]);

        // Sisa 1 unit, draft meminta 2 unit.
        $this->get($this->reviewRoute($this->camping))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('availability.available', 1)
                ->where('availability.requested', 2)
                ->where('availability.is_available', false)
            );
    }

    public function test_booking_dibatalkan_tidak_mengurangi_ketersediaan(): void
    {
        $this->createCompleteDraft($this->camping, $this->tent);

        $booking = Booking::factory()->forPeriod(
            $this->camping,
            '2026-10-10',
            '2026-10-12',
            BookingStatus::Dibatalkan,
        )->create();

        BookingItem::factory()->for($booking)->for($this->tent)->create(['quantity' => 4]);

        $this->get($this->reviewRoute($this->camping))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('availability.is_available', true));
    }

    public function test_review_menampilkan_jumlah_penyewa_untuk_unit_sepeda(): void
    {
        $this->createPeriodDraft($this->bike, $this->bicycle);
        $this->saveCustomer($this->bike, ['renter_count' => 3]);

        $this->get($this->reviewRoute($this->bike))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('isBikeRental', true)
                ->where('customer.renter_count', '3')
            );
    }

    public function test_review_tidak_menampilkan_jumlah_penyewa_untuk_camping(): void
    {
        $this->createPeriodDraft($this->camping, $this->tent, ['quantity' => 1]);
        $this->saveCustomer($this->camping);

        $this->get($this->reviewRoute($this->camping))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('isBikeRental', false)
                ->where('customer.renter_count', '')
            );
    }

    public function test_field_opsional_kosong_jadi_string_kosong(): void
    {
        $this->createPeriodDraft($this->camping, $this->tent);
        $this->saveCustomer($this->camping, ['email' => '', 'city' => '', 'notes' => '']);

        $this->get($this->reviewRoute($this->camping))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('customer.email', '')
                ->where('customer.city', '')
                ->where('customer.notes', '')
            );
    }

    public function test_tanpa_draft_review_menghasilkan_404(): void
    {
        $this->get($this->reviewRoute($this->camping))->assertNotFound();
    }

    public function test_draft_unit_lain_menghasilkan_404(): void
    {
        $this->createCompleteDraft($this->camping, $this->tent);

        $this->get($this->reviewRoute($this->bike))->assertNotFound();
    }

    public function test_produk_nonaktif_menghasilkan_404(): void
    {
        $this->createCompleteDraft($this->camping, $this->tent);

        $this->tent->update(['is_active' => false]);

        $this->get($this->reviewRoute($this->camping))->assertNotFound();
    }

    public function test_biodata_kosong_diarahkan_ke_form_biodata(): void
    {
        $this->createPeriodDraft($this->camping, $this->tent);

        $this->get($this->reviewRoute($this->camping))
            ->assertRedirect(route('booking.gokemping.biodata'));
    }

    public function test_biodata_kurang_lengkap_diarahkan_ke_form_biodata(): void
    {
        $this->createPeriodDraft($this->camping, $this->tent);

        session()->put(BookingDraft::SESSION_KEY, array_merge(
            session(BookingDraft::SESSION_KEY),
            ['customer' => [
                'name' => 'Zaki',
                'whatsapp' => '628123456789',
                'nik' => '',
                'address' => 'Garut',
            ]],
        ));

        $this->get($this->reviewRoute($this->camping))
            ->assertRedirect(route('booking.gokemping.biodata'));
    }

    public function test_biodata_lengkap_tidak_diarahkan(): void
    {
        $this->createCompleteDraft($this->camping, $this->tent);

        $this->get($this->reviewRoute($this->camping))->assertOk();
    }

    public function test_review_tidak_menulis_ke_database(): void
    {
        $this->createCompleteDraft($this->camping, $this->tent);

        $this->get($this->reviewRoute($this->camping))->assertOk();

        $this->assertDatabaseCount('bookings', 0);
        $this->assertDatabaseCount('booking_items', 0);
        $this->assertDatabaseCount('customers', 0);
    }

    public function test_review_tidak_membutuhkan_login(): void
    {
        $this->createCompleteDraft($this->camping, $this->tent);

        $this->assertGuest();

        $this->get($this->reviewRoute($this->camping))->assertOk();
    }

    public function test_alur_lengkap_dari_form_hingga_review(): void
    {
        $this->get(route('booking.gokemping.create', ['product' => $this->tent->slug]))
            ->assertOk();

        $this->createPeriodDraft($this->camping, $this->tent);
        $this->get(route('booking.gokemping.biodata'))->assertOk();
        $this->saveCustomer($this->camping);

        $this->get($this->reviewRoute($this->camping))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('pricing.total', 200000));
    }
}
