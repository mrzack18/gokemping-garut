<?php

namespace Tests\Feature;

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingCustomerLookupTest extends TestCase
{
    use RefreshDatabase;

    private const WHATSAPP = '628123456789';

    public function test_lookup_menemukan_pelanggan_lama_lewat_whatsapp(): void
    {
        Customer::factory()->withWhatsapp(self::WHATSAPP)->create([
            'name' => 'Budi Santoso',
        ]);

        $this->getJson(route('booking.customerLookup', [
            'whatsapp' => '08123456789',
        ]))
            ->assertOk()
            ->assertJsonPath('found', true)
            ->assertJsonPath('customer.name', 'Budi Santoso')
            ->assertJsonPath('customer.whatsapp', self::WHATSAPP)
            // NIK tidak ikut karena pemanggil tidak mengetik NIK-nya.
            ->assertJsonPath('customer.nik', null);
    }

    public function test_lookup_menemukan_pelanggan_lama_lewat_nik(): void
    {
        Customer::factory()->create([
            'nik' => '3201234567890001',
            'name' => 'Siti Aminah',
        ]);

        $this->getJson(route('booking.customerLookup', [
            'nik' => '3201234567890001',
        ]))
            ->assertOk()
            ->assertJsonPath('found', true)
            ->assertJsonPath('customer.name', 'Siti Aminah')
            // Pemanggil sudah mengetik NIK-nya, jadi tidak ada yang bocor.
            ->assertJsonPath('customer.nik', '3201234567890001');
    }

    public function test_whatsapp_menang_atas_nik(): void
    {
        Customer::factory()->withWhatsapp(self::WHATSAPP)->create([
            'nik' => '3201234567890001',
            'name' => 'Budi Santoso',
        ]);
        Customer::factory()->create([
            'whatsapp' => '628999999999',
            'nik' => '3276543210987654',
            'name' => 'Siti Aminah',
        ]);

        $this->getJson(route('booking.customerLookup', [
            'whatsapp' => self::WHATSAPP,
            'nik' => '3276543210987654',
        ]))
            ->assertOk()
            ->assertJsonPath('customer.name', 'Budi Santoso')
            ->assertJsonPath('customer.nik', null);
    }

    public function test_lookup_lewat_whatsapp_mengembalikan_biodata_tanpa_nik(): void
    {
        Customer::factory()->withWhatsapp(self::WHATSAPP)->create([
            'nik' => '3201234567890001',
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'address' => 'Jl. Merdeka No. 10',
            'city' => 'Bandung',
            'notes' => 'Pelanggan rutin',
        ]);

        $this->getJson(route('booking.customerLookup', [
            'whatsapp' => self::WHATSAPP,
        ]))
            ->assertOk()
            ->assertJsonPath('customer.email', 'budi@example.com')
            ->assertJsonPath('customer.nik', null)
            ->assertJsonPath('customer.address', 'Jl. Merdeka No. 10')
            ->assertJsonPath('customer.city', 'Bandung')
            ->assertJsonPath('customer.notes', 'Pelanggan rutin');
    }

    public function test_lookup_tidak_membocorkan_field_lain(): void
    {
        Customer::factory()->withWhatsapp(self::WHATSAPP)->create();

        $customer = $this->getJson(route('booking.customerLookup', [
            'whatsapp' => self::WHATSAPP,
        ]))
            ->assertOk()
            ->json('customer');

        $this->assertIsArray($customer);
        $this->assertSame(
            ['name', 'whatsapp', 'email', 'nik', 'address', 'city', 'notes'],
            array_keys($customer),
        );
    }

    public function test_lookup_tidak_ditemukan_jika_nomor_tidak_terdaftar(): void
    {
        Customer::factory()->withWhatsapp(self::WHATSAPP)->create();

        $this->getJson(route('booking.customerLookup', [
            'whatsapp' => '628999999999',
        ]))
            ->assertOk()
            ->assertJsonPath('found', false)
            ->assertJsonPath('customer', null);
    }

    public function test_lookup_tidak_ditemukan_jika_nik_tidak_terdaftar(): void
    {
        Customer::factory()->create(['nik' => '3201234567890001']);

        $this->getJson(route('booking.customerLookup', [
            'nik' => '3276543210987654',
        ]))
            ->assertOk()
            ->assertJsonPath('found', false);
    }

    public function test_lookup_mengabaikan_nomor_tidak_valid(): void
    {
        Customer::factory()->withWhatsapp(self::WHATSAPP)->create();

        $this->getJson(route('booking.customerLookup', [
            'whatsapp' => 'bukan-nomor',
        ]))
            ->assertOk()
            ->assertJsonPath('found', false);
    }

    public function test_lookup_mengabaikan_nik_bukan_16_digit(): void
    {
        Customer::factory()->create(['nik' => '3201234567890001']);

        $this->getJson(route('booking.customerLookup', [
            'nik' => '12345',
        ]))
            ->assertOk()
            ->assertJsonPath('found', false);
    }

    public function test_lookup_tanpa_parameter_tidak_mengembalikan_data(): void
    {
        Customer::factory()->withWhatsapp(self::WHATSAPP)->create();

        $this->getJson(route('booking.customerLookup'))
            ->assertOk()
            ->assertJsonPath('found', false)
            ->assertJsonPath('customer', null);
    }

    public function test_lookup_membalas_422_bila_parameter_terlalu_panjang(): void
    {
        $this->getJson(route('booking.customerLookup', [
            'whatsapp' => str_repeat('8', 40),
        ]))->assertUnprocessable()->assertJsonValidationErrors(['whatsapp']);
    }

    public function test_lookup_membalas_422_bila_nik_terlalu_panjang(): void
    {
        $this->getJson(route('booking.customerLookup', [
            'nik' => str_repeat('8', 20),
        ]))->assertUnprocessable()->assertJsonValidationErrors(['nik']);
    }

    public function test_lookup_dibatasi_throttle(): void
    {
        Customer::factory()->withWhatsapp(self::WHATSAPP)->create();

        for ($attempt = 0; $attempt < 30; $attempt++) {
            $this->getJson(route('booking.customerLookup', [
                'whatsapp' => self::WHATSAPP,
            ]))->assertOk();
        }

        $this->getJson(route('booking.customerLookup', [
            'whatsapp' => self::WHATSAPP,
        ]))->assertStatus(429);
    }

    public function test_lookup_tidak_membutuhkan_login(): void
    {
        Customer::factory()->withWhatsapp(self::WHATSAPP)->create();

        $this->assertGuest();

        $this->getJson(route('booking.customerLookup', [
            'whatsapp' => self::WHATSAPP,
        ]))
            ->assertOk()
            ->assertJsonPath('found', true);
    }

    public function test_lookup_bersifat_global_antar_unit_business(): void
    {
        // `customers` tidak punya `business_id`, jadi satu pelanggan bisa
        // ditemukan dari halaman mana pun.
        Customer::factory()->withWhatsapp(self::WHATSAPP)->create([
            'name' => 'Budi Santoso',
        ]);

        $this->assertFalse(
            in_array('business_id', Customer::query()->getConnection()->getSchemaBuilder()->getColumnListing('customers'), true)
        );

        $this->getJson(route('booking.customerLookup', [
            'whatsapp' => self::WHATSAPP,
        ]))
            ->assertOk()
            ->assertJsonPath('customer.name', 'Budi Santoso');
    }
}
