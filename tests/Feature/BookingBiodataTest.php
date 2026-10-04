<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Customer;
use App\Models\Product;
use App\Support\BookingDraft;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingBiodataTest extends TestCase
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
            'price' => 50000,
            'stock' => 3,
        ]);

        $this->bike = Business::factory()->create([
            'slug' => 'sewa-sepeda-garut',
            'is_active' => true,
        ]);

        $this->bicycle = Product::factory()->forBusiness($this->bike)->create([
            'slug' => 'sepeda-770',
            'price' => 25000,
            'stock' => 5,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createDraft(Business $business, Product $product, array $overrides = []): void
    {
        $this->post(route('booking.'.($business->slug === 'gokemping' ? 'gokemping' : 'sewaSepedaGarut').'.draft.store', [
            'product' => $product->slug,
        ]), array_merge([
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addDays(3)->toDateString(),
            'quantity' => 2,
        ], $overrides))->assertRedirect();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validCustomer(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Budi Santoso',
            'whatsapp' => '08123456789',
            'email' => 'budi@example.com',
            'nik' => '3201234567890001',
            'address' => 'Jl. Merdeka No. 10',
            'city' => 'Bandung',
            'notes' => 'Datang pagi',
        ], $overrides);
    }

    public function test_halaman_biodata_tampil_bila_draft_ada(): void
    {
        $this->createDraft($this->camping, $this->tent);

        $this->get(route('booking.gokemping.biodata'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('booking/biodata')
                ->where('product.name', $this->tent->name)
                ->where('draft.quantity', 2)
                ->where('draft.start_date', now()->addDay()->toDateString())
                ->where('draft.end_date', now()->addDays(3)->toDateString())
                ->where('customer.name', '')
                ->where('isBikeRental', false)
            );
    }

    public function test_halaman_biodata_tanpa_draft_menghasilkan_404(): void
    {
        $this->get(route('booking.gokemping.biodata'))->assertNotFound();
    }

    public function test_halaman_biodata_dengan_draft_unit_lain_menghasilkan_404(): void
    {
        $this->createDraft($this->camping, $this->tent);

        $this->get(route('booking.sewaSepedaGarut.biodata'))->assertNotFound();
    }

    public function test_halaman_biodata_dengan_produk_nonaktif_menghasilkan_404(): void
    {
        $this->createDraft($this->camping, $this->tent);

        $this->tent->update(['is_active' => false]);

        $this->get(route('booking.gokemping.biodata'))->assertNotFound();
    }

    public function test_halaman_biodata_menampilkan_flag_sewa_sepeda(): void
    {
        $this->createDraft($this->bike, $this->bicycle);

        $this->get(route('booking.sewaSepedaGarut.biodata'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('isBikeRental', true));
    }

    public function test_harga_ditampilkan_dari_produk_terkini(): void
    {
        $this->createDraft($this->camping, $this->tent);

        $this->tent->update(['price' => 75000]);

        $this->get(route('booking.gokemping.biodata'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('product.price', 75000));
    }

    public function test_biodata_tersimpan_di_session(): void
    {
        $this->createDraft($this->camping, $this->tent);

        $this->post(route('booking.gokemping.biodata.store'), $this->validCustomer())
            ->assertRedirect(route('booking.gokemping.review'));

        $customer = session(BookingDraft::SESSION_KEY)['customer'];

        $this->assertSame('Budi Santoso', $customer['name']);
        $this->assertSame('628123456789', $customer['whatsapp']);
        $this->assertSame('budi@example.com', $customer['email']);
        $this->assertSame('3201234567890001', $customer['nik']);
        $this->assertSame('Jl. Merdeka No. 10', $customer['address']);
        $this->assertSame('Bandung', $customer['city']);
        $this->assertSame('Datang pagi', $customer['notes']);
        $this->assertNull($customer['renter_count']);
    }

    public function test_biodata_tidak_menulis_ke_database(): void
    {
        $this->createDraft($this->camping, $this->tent);

        $this->post(route('booking.gokemping.biodata.store'), $this->validCustomer())
            ->assertRedirect();

        $this->assertDatabaseCount('customers', 0);
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_jumlah_penyewa_disimpan_untuk_unit_sepeda(): void
    {
        $this->createDraft($this->bike, $this->bicycle);

        $this->post(route('booking.sewaSepedaGarut.biodata.store'), $this->validCustomer([
            'renter_count' => 3,
        ]))->assertRedirect();

        $this->assertSame(3, session(BookingDraft::SESSION_KEY)['customer']['renter_count']);
    }

    public function test_jumlah_penyewa_diabaikan_untuk_unit_camping(): void
    {
        $this->createDraft($this->camping, $this->tent);

        $this->post(route('booking.gokemping.biodata.store'), $this->validCustomer([
            'renter_count' => 3,
        ]))->assertRedirect();

        $this->assertNull(session(BookingDraft::SESSION_KEY)['customer']['renter_count']);
    }

    public function test_biodata_tetap_ada_setelah_disimpan(): void
    {
        $this->createDraft($this->camping, $this->tent);

        $this->post(route('booking.gokemping.biodata.store'), $this->validCustomer())
            ->assertRedirect();

        $this->get(route('booking.gokemping.biodata'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('customer.name', 'Budi Santoso')
                ->where('customer.whatsapp', '628123456789')
                ->where('customer.nik', '3201234567890001')
                ->where('customer.city', 'Bandung')
            );
    }

    public function test_field_opsional_kosong_disimpan_sebagai_null(): void
    {
        $this->createDraft($this->camping, $this->tent);

        $this->post(route('booking.gokemping.biodata.store'), $this->validCustomer([
            'email' => '',
            'city' => '',
            'notes' => '',
        ]))->assertRedirect();

        $customer = session(BookingDraft::SESSION_KEY)['customer'];

        $this->assertNull($customer['email']);
        $this->assertNull($customer['city']);
        $this->assertNull($customer['notes']);
    }

    public function test_nama_wajib_diisi(): void
    {
        $this->createDraft($this->camping, $this->tent);

        $this->post(route('booking.gokemping.biodata.store'), $this->validCustomer(['name' => '']))
            ->assertSessionHasErrors('name');
    }

    public function test_whatsapp_wajib_diisi(): void
    {
        $this->createDraft($this->camping, $this->tent);

        $this->post(route('booking.gokemping.biodata.store'), $this->validCustomer(['whatsapp' => '']))
            ->assertSessionHasErrors('whatsapp');
    }

    public function test_whatsapp_tidak_valid_ditolak(): void
    {
        $this->createDraft($this->camping, $this->tent);

        $this->post(route('booking.gokemping.biodata.store'), $this->validCustomer([
            'whatsapp' => '12345',
        ]))->assertSessionHasErrors('whatsapp');
    }

    public function test_nik_wajib_diisi(): void
    {
        $this->createDraft($this->camping, $this->tent);

        $this->post(route('booking.gokemping.biodata.store'), $this->validCustomer(['nik' => '']))
            ->assertSessionHasErrors('nik');
    }

    public function test_nik_harus_16_digit(): void
    {
        $this->createDraft($this->camping, $this->tent);

        $this->post(route('booking.gokemping.biodata.store'), $this->validCustomer(['nik' => '12345']))
            ->assertSessionHasErrors('nik');
    }

    public function test_nik_dengan_spasi_setelah_trim_diterima(): void
    {
        $this->createDraft($this->camping, $this->tent);

        $this->post(route('booking.gokemping.biodata.store'), $this->validCustomer([
            'nik' => '  3201234567890001  ',
        ]))->assertRedirect(route('booking.gokemping.review'));
    }

    public function test_alamat_wajib_diisi(): void
    {
        $this->createDraft($this->camping, $this->tent);

        $this->post(route('booking.gokemping.biodata.store'), $this->validCustomer(['address' => '']))
            ->assertSessionHasErrors('address');
    }

    public function test_email_tidak_valid_ditolak(): void
    {
        $this->createDraft($this->camping, $this->tent);

        $this->post(route('booking.gokemping.biodata.store'), $this->validCustomer([
            'email' => 'bukan-email',
        ]))->assertSessionHasErrors('email');
    }

    public function test_email_kosong_diterima(): void
    {
        $this->createDraft($this->camping, $this->tent);

        $this->post(route('booking.gokemping.biodata.store'), $this->validCustomer([
            'email' => '',
        ]))->assertRedirect(route('booking.gokemping.review'));
    }

    public function test_jumlah_penyewa_nol_ditolak(): void
    {
        $this->createDraft($this->bike, $this->bicycle);

        $this->post(route('booking.sewaSepedaGarut.biodata.store'), $this->validCustomer([
            'renter_count' => 0,
        ]))->assertSessionHasErrors('renter_count');
    }

    public function test_jumlah_penyewa_lebih_dari_100_ditolak(): void
    {
        $this->createDraft($this->bike, $this->bicycle);

        $this->post(route('booking.sewaSepedaGarut.biodata.store'), $this->validCustomer([
            'renter_count' => 101,
        ]))->assertSessionHasErrors('renter_count');
    }

    public function test_penyimpanan_biodata_tanpa_draft_menghasilkan_404(): void
    {
        $this->post(route('booking.gokemping.biodata.store'), $this->validCustomer())
            ->assertNotFound();
    }

    public function test_penyimpanan_biodata_untuk_unit_lain_menghasilkan_404(): void
    {
        $this->createDraft($this->camping, $this->tent);

        $this->post(route('booking.sewaSepedaGarut.biodata.store'), $this->validCustomer())
            ->assertNotFound();
    }

    public function test_biodata_tidak_membutuhkan_login(): void
    {
        $this->createDraft($this->camping, $this->tent);

        $this->assertGuest();

        $this->get(route('booking.gokemping.biodata'))->assertOk();
        $this->post(route('booking.gokemping.biodata.store'), $this->validCustomer())->assertRedirect();
    }

    public function test_pelanggan_lama_bisa_dipakai_ulang(): void
    {
        Customer::factory()->withWhatsapp('628123456789')->create([
            'name' => 'Budi Santoso',
            'nik' => '3201234567890001',
            'address' => 'Jl. Merdeka No. 10',
            'city' => 'Bandung',
        ]);

        $this->createDraft($this->camping, $this->tent);

        $this->post(route('booking.gokemping.biodata.store'), $this->validCustomer([
            'notes' => 'Rebooking',
        ]))->assertRedirect();

        $this->assertDatabaseCount('customers', 1);
    }
}
