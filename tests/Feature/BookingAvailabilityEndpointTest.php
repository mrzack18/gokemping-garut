<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Business;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingAvailabilityEndpointTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Periode default yang diminta endpoint: mulai besok, selesai tiga hari
     * lagi. Semua tanggal dihitung dari `now()` supaya test tidak bergantung
     * pada tanggal saat test dijalankan.
     */
    public function test_endpoint_ketersediaan_tersedia_di_kedua_unit(): void
    {
        $gokemping = $this->business('gokemping');
        $sepeda = $this->business('sewa-sepeda-garut');

        $tenda = $this->product($gokemping, ['stock' => 5]);
        $sepedaProduct = $this->product($sepeda, ['stock' => 2]);

        $this->getJson($this->url('booking.gokemping.availability', $tenda))
            ->assertOk()
            ->assertJsonPath('product_id', $tenda->id)
            ->assertJsonPath('stock', 5);

        $this->getJson($this->url('booking.sewaSepedaGarut.availability', $sepedaProduct))
            ->assertOk()
            ->assertJsonPath('product_id', $sepedaProduct->id)
            ->assertJsonPath('stock', 2);
    }

    public function test_endpoint_tidak_membutuhkan_login(): void
    {
        $business = $this->business('gokemping');
        $product = $this->product($business, ['stock' => 3]);

        $this->assertGuest();

        $this->getJson($this->url('booking.gokemping.availability', $product))
            ->assertOk()
            ->assertJsonPath('available', 3)
            ->assertJsonPath('used', 0);
    }

    public function test_endpoint_mengembalikan_angka_ketersediaan_setelah_ada_booking(): void
    {
        $business = $this->business('gokemping');
        $product = $this->product($business, ['stock' => 5]);

        $this->booking($business, $product, 3, $this->start(), $this->end());

        $this->getJson($this->url('booking.gokemping.availability', $product))
            ->assertOk()
            ->assertJsonPath('stock', 5)
            ->assertJsonPath('used', 3)
            ->assertJsonPath('available', 2)
            ->assertJsonPath('message', 'Tersedia 2 unit pada periode tersebut.');
    }

    public function test_endpoint_menandai_tidak_cukup_bila_jumlah_diminta_lebih_besar(): void
    {
        $business = $this->business('gokemping');
        $product = $this->product($business, ['stock' => 5]);

        $this->booking($business, $product, 3, $this->start(), $this->end());

        $this->getJson($this->url('booking.gokemping.availability', $product, [
            'quantity' => 3,
        ]))
            ->assertOk()
            ->assertJsonPath('available', 2)
            ->assertJsonPath('requested', 3)
            ->assertJsonPath('is_available', false)
            ->assertJsonPath('message', 'Stok tidak mencukupi pada periode tersebut.');
    }

    public function test_endpoint_mengembalikan_pesan_kosong_bila_tidak_ada_unit(): void
    {
        $business = $this->business('gokemping');
        $product = $this->product($business, ['stock' => 2]);

        $this->booking($business, $product, 2, $this->start(), $this->end());

        $this->getJson($this->url('booking.gokemping.availability', $product))
            ->assertOk()
            ->assertJsonPath('available', 0)
            ->assertJsonPath('is_available', false)
            ->assertJsonPath('message', 'Stok tidak tersedia pada periode tersebut.');
    }

    public function test_endpoint_melaporkan_status_booking_yang_menahan_stok(): void
    {
        $business = $this->business('gokemping');
        $product = $this->product($business, ['stock' => 2]);

        $this->getJson($this->url('booking.gokemping.availability', $product))
            ->assertOk()
            ->assertJsonPath('holding_statuses', [
                'menunggu_konfirmasi',
                'dikonfirmasi',
                'sedang_disewa',
            ]);
    }

    public function test_endpoint_mengembalikan_periode_yang_diminta(): void
    {
        $business = $this->business('gokemping');
        $product = $this->product($business, ['stock' => 2]);

        $this->getJson($this->url('booking.gokemping.availability', $product))
            ->assertOk()
            ->assertJsonPath('start_date', $this->start())
            ->assertJsonPath('end_date', $this->end());
    }

    public function test_endpoint_mengabaikan_booking_yang_sudah_selesai_atau_dibatalkan(): void
    {
        $business = $this->business('gokemping');
        $product = $this->product($business, ['stock' => 3]);

        $this->booking($business, $product, 3, $this->start(), $this->end(), BookingStatus::Selesai);
        $this->booking($business, $product, 3, $this->start(), $this->end(), BookingStatus::Dibatalkan);

        $this->getJson($this->url('booking.gokemping.availability', $product))
            ->assertOk()
            ->assertJsonPath('used', 0)
            ->assertJsonPath('available', 3);
    }

    public function test_endpoint_menghitung_booking_yang_menunggu_konfirmasi(): void
    {
        $business = $this->business('gokemping');
        $product = $this->product($business, ['stock' => 3]);

        $this->booking($business, $product, 2, $this->start(), $this->end(), BookingStatus::MenungguKonfirmasi);

        $this->getJson($this->url('booking.gokemping.availability', $product))
            ->assertOk()
            ->assertJsonPath('used', 2)
            ->assertJsonPath('available', 1);
    }

    public function test_endpoint_tidak_menghitung_booking_di_periode_lain(): void
    {
        $business = $this->business('gokemping');
        $product = $this->product($business, ['stock' => 3]);

        $this->booking(
            $business,
            $product,
            3,
            now()->addDays(10)->toDateString(),
            now()->addDays(13)->toDateString(),
        );

        $this->getJson($this->url('booking.gokemping.availability', $product))
            ->assertOk()
            ->assertJsonPath('used', 0)
            ->assertJsonPath('available', 3);
    }

    /**
     * Tanggal selesai eksklusif: barang yang kembali hari ini sudah bisa
     * disewa lagi pada tanggal yang sama.
     */
    public function test_endpoint_melepas_stok_pada_tanggal_pengembalian(): void
    {
        $business = $this->business('gokemping');
        $product = $this->product($business, ['stock' => 2]);

        $this->booking($business, $product, 2, $this->start(), now()->addDays(2)->toDateString());

        $this->getJson($this->url('booking.gokemping.availability', $product, [
            'start_date' => now()->addDays(2)->toDateString(),
            'end_date' => now()->addDays(4)->toDateString(),
        ]))
            ->assertOk()
            ->assertJsonPath('used', 0)
            ->assertJsonPath('available', 2);
    }

    public function test_endpoint_membalas_422_bila_tanggal_kurang(): void
    {
        $business = $this->business('gokemping');
        $product = $this->product($business, ['stock' => 2]);

        $this->getJson(route('booking.gokemping.availability', [
            'product' => $product->slug,
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['start_date', 'end_date']);
    }

    public function test_endpoint_membalas_422_bila_format_tanggal_salah(): void
    {
        $business = $this->business('gokemping');
        $product = $this->product($business, ['stock' => 2]);

        $this->getJson(route('booking.gokemping.availability', [
            'product' => $product->slug,
        ]).'?start_date=10-10-2026&end_date=12-10-2026')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['start_date', 'end_date']);
    }

    public function test_endpoint_membalas_422_bila_tanggal_selesai_sebelum_mulai(): void
    {
        $business = $this->business('gokemping');
        $product = $this->product($business, ['stock' => 2]);

        $this->getJson(route('booking.gokemping.availability', [
            'product' => $product->slug,
        ]).'?start_date='.now()->addDays(5)->toDateString().'&end_date='.now()->addDays(2)->toDateString())
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['end_date']);
    }

    public function test_endpoint_membalas_422_bila_tanggal_mulai_di_masa_lalu(): void
    {
        $business = $this->business('gokemping');
        $product = $this->product($business, ['stock' => 2]);

        $this->getJson(route('booking.gokemping.availability', [
            'product' => $product->slug,
        ]).'?start_date='.now()->subDay()->toDateString().'&end_date='.now()->addDay()->toDateString())
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['start_date'])
            ->assertJsonPath('message', 'Tanggal mulai tidak boleh di masa lalu.');
    }

    public function test_endpoint_membalas_422_bila_jumlah_tidak_valid(): void
    {
        $business = $this->business('gokemping');
        $product = $this->product($business, ['stock' => 2]);

        $this->getJson(route('booking.gokemping.availability', [
            'product' => $product->slug,
        ]).'?quantity=0')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['quantity']);
    }

    public function test_endpoint_membalas_404_bila_produk_tak_ada(): void
    {
        $this->business('gokemping');

        $this->getJson($this->url('booking.gokemping.availability', 'tidak-ada'))
            ->assertNotFound();
    }

    public function test_endpoint_membalas_404_bila_produk_nonaktif(): void
    {
        $business = $this->business('gokemping');
        $product = $this->product($business, ['is_active' => false]);

        $this->getJson($this->url('booking.gokemping.availability', $product))
            ->assertNotFound();
    }

    public function test_endpoint_membalas_404_bila_unit_nonaktif(): void
    {
        $business = $this->business('gokemping', isActive: false);
        $product = $this->product($business);

        $this->getJson($this->url('booking.gokemping.availability', $product))
            ->assertNotFound();
    }

    public function test_endpoint_membalas_404_bila_produk_milik_unit_lain(): void
    {
        $gokemping = $this->business('gokemping');
        $sepeda = $this->business('sewa-sepeda-garut');

        $this->product($gokemping);
        $foreign = $this->product($sepeda, ['slug' => 'sepeda-mtb']);

        $this->getJson($this->url('booking.gokemping.availability', $foreign))
            ->assertNotFound();
    }

    public function test_endpoint_tidak_menghitung_booking_unit_lain(): void
    {
        $gokemping = $this->business('gokemping');
        $sepeda = $this->business('sewa-sepeda-garut');

        $product = $this->product($gokemping, ['stock' => 2]);

        $this->booking($sepeda, $product, 2, $this->start(), $this->end());

        $this->getJson($this->url('booking.gokemping.availability', $product))
            ->assertOk()
            ->assertJsonPath('used', 0)
            ->assertJsonPath('available', 2);
    }

    public function test_endpoint_meloloskan_admin_yang_login(): void
    {
        $gokemping = $this->business('gokemping');
        $sepeda = $this->business('sewa-sepeda-garut');

        $product = $this->product($sepeda, ['stock' => 4]);

        $this->booking($sepeda, $product, 1, $this->start(), $this->end());

        $this->actingAs(User::factory()->forBusiness($gokemping)->create())
            ->getJson($this->url('booking.sewaSepedaGarut.availability', $product))
            ->assertOk()
            ->assertJsonPath('used', 1)
            ->assertJsonPath('available', 3);
    }

    public function test_endpoint_tidak_menulis_booking_baru(): void
    {
        $business = $this->business('gokemping');
        $product = $this->product($business, ['stock' => 2]);

        $before = Booking::count();

        $this->getJson($this->url('booking.gokemping.availability', $product))
            ->assertOk();

        $this->assertSame($before, Booking::count());
    }

    private function start(): string
    {
        return now()->addDay()->toDateString();
    }

    private function end(): string
    {
        return now()->addDays(3)->toDateString();
    }

    /**
     * @param  array<string, string>  $query
     */
    private function url(string $routeName, Product|string $product, array $query = []): string
    {
        $slug = $product instanceof Product ? $product->slug : $product;

        return route($routeName, array_merge(
            [
                'product' => $slug,
                'start_date' => $this->start(),
                'end_date' => $this->end(),
            ],
            $query,
        ));
    }

    private function business(string $slug, bool $isActive = true): Business
    {
        return Business::factory()->create([
            'slug' => $slug,
            'is_active' => $isActive,
        ]);
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
