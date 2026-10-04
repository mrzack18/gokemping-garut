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

class BookingDraftTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::factory()->create([
            'slug' => 'gokemping',
            'is_active' => true,
        ]);

        $this->product = Product::factory()->forBusiness($this->business)->create([
            'slug' => 'tenda-4-orang',
            'stock' => 3,
        ]);
    }

    public function test_draft_tersimpan_di_session(): void
    {
        $this->post(route('booking.gokemping.draft.store', ['product' => $this->product->slug]), [
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addDays(3)->toDateString(),
            'quantity' => 2,
        ])->assertRedirect(route('booking.gokemping.biodata'));

        $draft = session(BookingDraft::SESSION_KEY);

        $this->assertSame('gokemping', $draft['business']);
        $this->assertSame((int) $this->product->getKey(), $draft['product_id']);
        $this->assertSame(now()->addDay()->toDateString(), $draft['start_date']);
        $this->assertSame(now()->addDays(3)->toDateString(), $draft['end_date']);
        $this->assertSame(2, $draft['quantity']);
    }

    public function test_draft_tidak_menulis_ke_database(): void
    {
        $this->post(route('booking.gokemping.draft.store', ['product' => $this->product->slug]), [
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addDays(3)->toDateString(),
            'quantity' => 1,
        ])->assertRedirect();

        $this->assertDatabaseCount('bookings', 0);
        $this->assertDatabaseCount('booking_items', 0);
    }

    public function test_jumlah_melebihi_stok_ditolak_dan_draft_tidak_disimpan(): void
    {
        $response = $this->post(route('booking.gokemping.draft.store', ['product' => $this->product->slug]), [
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addDays(3)->toDateString(),
            'quantity' => 4,
        ]);

        $response->assertSessionHasErrors('quantity');

        $this->assertNull(session(BookingDraft::SESSION_KEY));
    }

    public function test_stok_terpakai_menolak_draft(): void
    {
        $booking = Booking::factory()->forPeriod(
            $this->business,
            now()->addDay()->toDateString(),
            now()->addDays(3)->toDateString(),
            BookingStatus::Dikonfirmasi,
        )->create();

        BookingItem::factory()->for($booking)->for($this->product)->create(['quantity' => 3]);

        $this->post(route('booking.gokemping.draft.store', ['product' => $this->product->slug]), [
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addDays(3)->toDateString(),
            'quantity' => 1,
        ])->assertSessionHasErrors('quantity');

        $this->assertNull(session(BookingDraft::SESSION_KEY));
    }

    public function test_booking_dengan_tanggal_sudah_melewati_tidak_mengurangi_stok(): void
    {
        $booking = Booking::factory()->forPeriod(
            $this->business,
            now()->subDays(5)->toDateString(),
            now()->subDays(2)->toDateString(),
            BookingStatus::Dikonfirmasi,
        )->create();

        BookingItem::factory()->for($booking)->for($this->product)->create(['quantity' => 3]);

        $this->post(route('booking.gokemping.draft.store', ['product' => $this->product->slug]), [
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addDays(3)->toDateString(),
            'quantity' => 3,
        ])->assertRedirect(route('booking.gokemping.biodata'));
    }

    public function test_status_batal_tidak_mengurangi_stok(): void
    {
        $booking = Booking::factory()->forPeriod(
            $this->business,
            now()->addDay()->toDateString(),
            now()->addDays(3)->toDateString(),
            BookingStatus::Dibatalkan,
        )->create();

        BookingItem::factory()->for($booking)->for($this->product)->create(['quantity' => 3]);

        $this->post(route('booking.gokemping.draft.store', ['product' => $this->product->slug]), [
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addDays(3)->toDateString(),
            'quantity' => 3,
        ])->assertRedirect(route('booking.gokemping.biodata'));
    }

    public function test_tanggal_mulai_wajib_ada(): void
    {
        $this->post(route('booking.gokemping.draft.store', ['product' => $this->product->slug]), [
            'end_date' => now()->addDays(3)->toDateString(),
            'quantity' => 1,
        ])->assertSessionHasErrors('start_date');
    }

    public function test_tanggal_selesai_wajib_ada(): void
    {
        $this->post(route('booking.gokemping.draft.store', ['product' => $this->product->slug]), [
            'start_date' => now()->addDay()->toDateString(),
            'quantity' => 1,
        ])->assertSessionHasErrors('end_date');
    }

    public function test_format_tanggal_harus_valid(): void
    {
        $this->post(route('booking.gokemping.draft.store', ['product' => $this->product->slug]), [
            'start_date' => '01-01-2026',
            'end_date' => now()->addDays(3)->toDateString(),
            'quantity' => 1,
        ])->assertSessionHasErrors('start_date');
    }

    public function test_tanggal_mulai_di_masa_lalu_ditolak(): void
    {
        $this->post(route('booking.gokemping.draft.store', ['product' => $this->product->slug]), [
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDays(3)->toDateString(),
            'quantity' => 1,
        ])->assertSessionHasErrors('start_date');
    }

    public function test_tanggal_selesai_sebelum_tanggal_mulai_ditolak(): void
    {
        $this->post(route('booking.gokemping.draft.store', ['product' => $this->product->slug]), [
            'start_date' => now()->addDays(3)->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'quantity' => 1,
        ])->assertSessionHasErrors('end_date');
    }

    public function test_tanggal_sama_diterima(): void
    {
        $this->post(route('booking.gokemping.draft.store', ['product' => $this->product->slug]), [
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'quantity' => 1,
        ])->assertRedirect(route('booking.gokemping.biodata'));
    }

    public function test_jumlah_wajib_ada(): void
    {
        $this->post(route('booking.gokemping.draft.store', ['product' => $this->product->slug]), [
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addDays(3)->toDateString(),
        ])->assertSessionHasErrors('quantity');
    }

    public function test_jumlah_nol_ditolak(): void
    {
        $this->post(route('booking.gokemping.draft.store', ['product' => $this->product->slug]), [
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addDays(3)->toDateString(),
            'quantity' => 0,
        ])->assertSessionHasErrors('quantity');
    }

    public function test_jumlah_bukan_bulat_ditolak(): void
    {
        $this->post(route('booking.gokemping.draft.store', ['product' => $this->product->slug]), [
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addDays(3)->toDateString(),
            'quantity' => 'dua',
        ])->assertSessionHasErrors('quantity');
    }

    public function test_produk_milik_unit_lain_ditolak(): void
    {
        $otherBusiness = Business::factory()->create(['slug' => 'sewa-sepeda-garut', 'is_active' => true]);
        $otherProduct = Product::factory()->forBusiness($otherBusiness)->create(['slug' => 'sepeda-770']);

        $this->post(route('booking.gokemping.draft.store', ['product' => $otherProduct->slug]), [
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addDays(3)->toDateString(),
            'quantity' => 1,
        ])->assertNotFound();

        $this->assertNull(session(BookingDraft::SESSION_KEY));
    }

    public function test_produk_nonaktif_ditolak(): void
    {
        $this->product->update(['is_active' => false]);

        $this->post(route('booking.gokemping.draft.store', ['product' => $this->product->slug]), [
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addDays(3)->toDateString(),
            'quantity' => 1,
        ])->assertNotFound();
    }

    public function test_unit_bisnis_tidak_aktif_ditolak(): void
    {
        $this->business->update(['is_active' => false]);

        $this->post(route('booking.gokemping.draft.store', ['product' => $this->product->slug]), [
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addDays(3)->toDateString(),
            'quantity' => 1,
        ])->assertNotFound();
    }

    public function test_produk_tidak_ada_ditolak(): void
    {
        $this->post(route('booking.gokemping.draft.store', ['product' => 'tidak-ada']), [
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addDays(3)->toDateString(),
            'quantity' => 1,
        ])->assertNotFound();
    }

    public function test_draft_untuk_unit_sepeda_menunjuk_route_biodata_sepeda(): void
    {
        $bike = Business::factory()->create(['slug' => 'sewa-sepeda-garut', 'is_active' => true]);
        $bikeProduct = Product::factory()->forBusiness($bike)->create([
            'slug' => 'sepeda-770',
            'stock' => 5,
        ]);

        $this->post(route('booking.sewaSepedaGarut.draft.store', ['product' => $bikeProduct->slug]), [
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addDays(3)->toDateString(),
            'quantity' => 2,
        ])->assertRedirect(route('booking.sewaSepedaGarut.biodata'));
    }

    public function test_draft_lama_pertahankan_data_customer(): void
    {
        $this->post(route('booking.gokemping.draft.store', ['product' => $this->product->slug]), [
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addDays(3)->toDateString(),
            'quantity' => 1,
        ]);

        session()->put(BookingDraft::SESSION_KEY, array_merge(
            session(BookingDraft::SESSION_KEY),
            ['customer' => ['name' => 'Budi Santoso', 'whatsapp' => '628123456789']],
        ));

        $this->post(route('booking.gokemping.draft.store', ['product' => $this->product->slug]), [
            'start_date' => now()->addDays(2)->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
            'quantity' => 2,
        ])->assertRedirect();

        $draft = session(BookingDraft::SESSION_KEY);

        $this->assertSame('Budi Santoso', $draft['customer']['name']);
        $this->assertSame(2, $draft['quantity']);
        $this->assertSame(now()->addDays(2)->toDateString(), $draft['start_date']);
    }

    public function test_form_booking_menerima_nilai_awal_dari_draft(): void
    {
        $this->post(route('booking.gokemping.draft.store', ['product' => $this->product->slug]), [
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addDays(3)->toDateString(),
            'quantity' => 2,
        ]);

        $this->get(route('booking.gokemping.create', ['product' => $this->product->slug]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('booking/form')
                ->where('initial.start_date', now()->addDay()->toDateString())
                ->where('initial.end_date', now()->addDays(3)->toDateString())
                ->where('initial.quantity', 2)
            );
    }

    public function test_form_booking_tanpa_draft_menggunakan_nilai_awal_kosong(): void
    {
        $this->get(route('booking.gokemping.create', ['product' => $this->product->slug]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('initial.start_date', '')
                ->where('initial.end_date', '')
                ->where('initial.quantity', 1)
            );
    }

    public function test_form_booking_untuk_unit_lain_tidak_memakai_draft(): void
    {
        $bike = Business::factory()->create(['slug' => 'sewa-sepeda-garut', 'is_active' => true]);
        $bikeProduct = Product::factory()->forBusiness($bike)->create(['slug' => 'sepeda-770']);

        $this->post(route('booking.gokemping.draft.store', ['product' => $this->product->slug]), [
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addDays(3)->toDateString(),
            'quantity' => 2,
        ]);

        $this->get(route('booking.sewaSepedaGarut.create', ['product' => $bikeProduct->slug]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('initial.quantity', 1));
    }

    public function test_draft_produk_lain_dalam_unit_yang_sama_tidak_dipakai(): void
    {
        $otherProduct = Product::factory()->forBusiness($this->business)->create([
            'slug' => 'tenda-2-orang',
            'stock' => 2,
        ]);

        $this->post(route('booking.gokemping.draft.store', ['product' => $this->product->slug]), [
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addDays(3)->toDateString(),
            'quantity' => 2,
        ]);

        $this->get(route('booking.gokemping.create', ['product' => $otherProduct->slug]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('initial.quantity', 1));
    }
}
