<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route as RouteFacade;
use Tests\TestCase;

/**
 * Cek tiket publik (kode booking + nomor WhatsApp).
 *
 * Halaman ini lintas unit dan tanpa login, jadi test-nya fokus ke verifikasi
 * identitas, normalisasi input, dan data yang tidak boleh ikut dikirim.
 */
class TicketCheckTest extends TestCase
{
    use RefreshDatabase;

    private const WHATSAPP = '628123456789';

    public function test_landing_page_menampilkan_section_cek_tiket_tanpa_tiket(): void
    {
        $this->assertGuest();

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('welcome')
                ->where('ticket', null)
                ->has('businesses')
            );
    }

    public function test_tiket_ditemukan_dengan_kode_dan_whatsapp_yang_benar(): void
    {
        $business = Business::factory()->create(['name' => 'GoKemping']);
        $booking = $this->booking($business);

        $this->post(route('tickets.lookup'), [
            'booking_code' => $booking->booking_code,
            'whatsapp' => self::WHATSAPP,
        ])
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('welcome')
                ->where('ticket.booking_code', $booking->booking_code)
                ->where('ticket.business.name', 'GoKemping')
                ->where('ticket.customer_name', 'Budi Santoso')
                ->where('ticket.status', BookingStatus::MenungguKonfirmasi->value)
                ->where('ticket.status_label', 'Menunggu Konfirmasi')
                ->where('ticket.payment_status_label', 'Menunggu Verifikasi')
                ->where('ticket.payment_method_label', 'Cash')
                ->has('ticket.items', 1)
                ->where('ticket.items.0.product_name', 'Tenda Dome 4 Person')
                ->where('ticket.items.0.quantity', 2)
                ->where('ticket.total_label', '150.000')
                ->where('ticket.period.total_days_label', '2 hari')
            );
    }

    public function test_kode_booking_tidak_peka_huruf_besar_kecil(): void
    {
        $booking = $this->booking(Business::factory()->create());

        $this->post(route('tickets.lookup'), [
            'booking_code' => strtolower((string) $booking->booking_code),
            'whatsapp' => self::WHATSAPP,
        ])
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('ticket.booking_code', $booking->booking_code)
            );
    }

    public function test_nomor_whatsapp_dinormalkan_sebelum_dicocokkan(): void
    {
        $booking = $this->booking(Business::factory()->create());

        foreach (['08123456789', '+62 812-3456-789', '628123456789'] as $input) {
            $this->post(route('tickets.lookup'), [
                'booking_code' => $booking->booking_code,
                'whatsapp' => $input,
            ])
                ->assertOk()
                ->assertInertia(fn ($page) => $page
                    ->where('ticket.booking_code', $booking->booking_code)
                );
        }
    }

    public function test_whatsapp_yang_tidak_cocok_tidak_membuka_tiket(): void
    {
        $booking = $this->booking(Business::factory()->create());

        $this->from(route('home'))
            ->post(route('tickets.lookup'), [
                'booking_code' => $booking->booking_code,
                'whatsapp' => '628999999999',
            ])
            ->assertRedirect(route('home'))
            ->assertSessionHasErrors('booking_code');
    }

    public function test_tiket_milik_penyewa_lain_tidak_terbuka(): void
    {
        $business = Business::factory()->create();
        $booking = $this->booking($business);
        Customer::factory()->withWhatsapp('628999999999')->create();

        $this->from(route('home'))
            ->post(route('tickets.lookup'), [
                'booking_code' => $booking->booking_code,
                'whatsapp' => '628999999999',
            ])
            ->assertSessionHasErrors('booking_code');
    }

    public function test_kode_yang_tidak_dikenal_menghasilkan_error(): void
    {
        $this->from(route('home'))
            ->post(route('tickets.lookup'), [
                'booking_code' => 'GK-99999999-999',
                'whatsapp' => self::WHATSAPP,
            ])
            ->assertSessionHasErrors('booking_code');
    }

    public function test_nomor_whatsapp_tidak_valid_menghasilkan_error(): void
    {
        $booking = $this->booking(Business::factory()->create());

        $this->from(route('home'))
            ->post(route('tickets.lookup'), [
                'booking_code' => $booking->booking_code,
                'whatsapp' => 'bukan-nomor',
            ])
            ->assertSessionHasErrors('whatsapp');
    }

    public function test_kode_dan_nomor_wajib_diisi(): void
    {
        $this->from(route('home'))
            ->post(route('tickets.lookup'), [])
            ->assertSessionHasErrors(['booking_code', 'whatsapp']);
    }

    public function test_tiket_tidak_memuat_nik_maupun_alamat(): void
    {
        $booking = $this->booking(Business::factory()->create());

        $this->post(route('tickets.lookup'), [
            'booking_code' => $booking->booking_code,
            'whatsapp' => self::WHATSAPP,
        ])
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->missing('ticket.nik')
                ->missing('ticket.address')
                ->where('ticket.customer_name', 'Budi Santoso')
            );
    }

    public function test_admin_unit_lain_tetap_bisa_cek_tiket(): void
    {
        $booking = $this->booking(Business::factory()->create());
        $otherAdmin = User::factory()->create();

        $this->actingAs($otherAdmin)
            ->post(route('tickets.lookup'), [
                'booking_code' => $booking->booking_code,
                'whatsapp' => self::WHATSAPP,
            ])
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('ticket.booking_code', $booking->booking_code)
            );
    }

    public function test_tiket_yang_dibatalkan_menampilkan_alasannya(): void
    {
        $booking = $this->booking(
            Business::factory()->create(),
            status: BookingStatus::Dibatalkan,
            cancellationReason: 'Penyewa tidak muncul di lokasi',
        );

        $this->post(route('tickets.lookup'), [
            'booking_code' => $booking->booking_code,
            'whatsapp' => self::WHATSAPP,
        ])
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('ticket.cancellation_reason', 'Penyewa tidak muncul di lokasi')
            );
    }

    public function test_pencarian_dibatasi_throttle(): void
    {
        $booking = $this->booking(Business::factory()->create());

        for ($attempt = 0; $attempt < 30; $attempt++) {
            $this->post(route('tickets.lookup'), [
                'booking_code' => $booking->booking_code,
                'whatsapp' => self::WHATSAPP,
            ])->assertOk();
        }

        $this->post(route('tickets.lookup'), [
            'booking_code' => $booking->booking_code,
            'whatsapp' => self::WHATSAPP,
        ])->assertStatus(429);
    }

    /**
     * Halaman cek tiket tidak boleh membuka data lewat GET; pencarian hanya
     * lewat POST supaya kode booking dan nomor tidak ikut tercatat di URL.
     */
    public function test_pencarian_tidak_tersedia_lewat_get(): void
    {
        $methods = collect(RouteFacade::getRoutes())
            ->first(fn ($route): bool => $route->getName() === 'tickets.lookup')
            ?->methods() ?? [];

        $this->assertSame(['POST'], $methods);
    }

    private function booking(
        Business $business,
        BookingStatus $status = BookingStatus::MenungguKonfirmasi,
        ?string $cancellationReason = null,
    ): Booking {
        $customer = Customer::factory()->withWhatsapp(self::WHATSAPP)->create([
            'name' => 'Budi Santoso',
        ]);

        $booking = Booking::factory()->forBusiness($business)->create([
            'customer_id' => $customer->getKey(),
            'booking_status' => $status,
            'payment_status' => PaymentStatus::MenungguVerifikasi,
            'payment_method' => 'cash',
            'cancellation_reason' => $cancellationReason,
            'total_days' => 2,
            'subtotal' => 150000,
            'total' => 150000,
            'start_date' => '2026-10-10',
            'end_date' => '2026-10-12',
        ]);

        BookingItem::factory()->create([
            'booking_id' => $booking->getKey(),
            'product_name' => 'Tenda Dome 4 Person',
            'quantity' => 2,
            'subtotal' => 150000,
        ]);

        Payment::factory()->create([
            'booking_id' => $booking->getKey(),
            'business_id' => $business->getKey(),
            'method' => 'cash',
            'amount' => 150000,
            'status' => PaymentStatus::MenungguVerifikasi,
        ]);

        return $booking;
    }
}
