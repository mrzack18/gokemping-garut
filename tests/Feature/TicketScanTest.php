<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\User;
use App\Support\TicketToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pindai QR tiket (ROADMAP 5.5 lanjutan).
 *
 * QR berisi URL bertanda tangan. Halaman publik membukanya lintas unit,
 * sedangkan halaman panel admin tetap terbatas pada unit admin yang login.
 */
class TicketScanTest extends TestCase
{
    use RefreshDatabase;

    public function test_scan_url_menampilkan_tiket_tanpa_login(): void
    {
        $this->assertGuest();

        $booking = $this->booking(Business::factory()->create(['name' => 'GoKemping']));

        $this->get(TicketToken::url((string) $booking->booking_code))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('welcome')
                ->where('ticket.booking_code', $booking->booking_code)
                ->where('ticket.customer_name', 'Budi Santoso')
                ->where('ticket.business.name', 'GoKemping')
                ->missing('ticket.nik')
                ->missing('ticket.address')
            );
    }

    public function test_scan_tidak_di_cache_dan_tidak_diindeks(): void
    {
        $booking = $this->booking(Business::factory()->create());

        $this->get(TicketToken::url((string) $booking->booking_code))
            ->assertOk()
            ->assertHeader('cache-control', 'must-revalidate, no-cache, no-store, private')
            ->assertHeader('referrer-policy', 'no-referrer')
            ->assertHeader('x-robots-tag', 'noindex, nofollow');
    }

    public function test_scan_dengan_token_salah_menghasilkan_404(): void
    {
        $booking = $this->booking(Business::factory()->create());

        $this->get(route('tickets.scan', [
            'booking' => $booking->booking_code,
            'token' => 'token-palsu',
        ]))->assertNotFound();
    }

    public function test_scan_tanpa_token_menghasilkan_404(): void
    {
        $booking = $this->booking(Business::factory()->create());

        $this->get(route('tickets.scan', [
            'booking' => $booking->booking_code,
        ]))->assertNotFound();
    }

    public function test_scan_kode_yang_tidak_dikenal_menghasilkan_404(): void
    {
        $code = 'GK-99999999-999';

        $this->get(TicketToken::url($code))->assertNotFound();
    }

    public function test_scan_di_panel_admin_menampilkan_tiket_unit_sendiri(): void
    {
        $user = User::factory()->create();
        $booking = $this->booking($user->business);

        $this->actingAs($user)
            ->get(route('admin.tickets.scan', [
                'booking' => $booking->booking_code,
                'token' => TicketToken::make((string) $booking->booking_code),
            ]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/tickets/index')
                ->where('ticket.booking_code', $booking->booking_code)
                ->missing('ticket.nik')
            );
    }

    public function test_scan_di_panel_admin_menolak_tiket_unit_lain(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $foreign = $this->booking($other->business);

        $this->actingAs($user)
            ->get(route('admin.tickets.scan', [
                'booking' => $foreign->booking_code,
                'token' => TicketToken::make((string) $foreign->booking_code),
            ]))
            ->assertNotFound();
    }

    public function test_scan_di_panel_admin_butuh_login(): void
    {
        $booking = $this->booking(Business::factory()->create());

        $this->get(route('admin.tickets.scan', [
            'booking' => $booking->booking_code,
            'token' => TicketToken::make((string) $booking->booking_code),
        ]))->assertRedirect(route('login'));
    }

    public function test_scan_di_panel_admin_menolak_token_palsu(): void
    {
        $user = User::factory()->create();
        $booking = $this->booking($user->business);

        $this->actingAs($user)
            ->get(route('admin.tickets.scan', [
                'booking' => $booking->booking_code,
                'token' => 'token-palsu',
            ]))
            ->assertNotFound();
    }

    private function booking(Business $business): Booking
    {
        $customer = Customer::factory()->withWhatsapp('628123456789')->create([
            'name' => 'Budi Santoso',
        ]);

        $booking = Booking::factory()->forBusiness($business)->create([
            'customer_id' => $customer->getKey(),
            'payment_status' => PaymentStatus::MenungguVerifikasi,
            'payment_method' => 'cash',
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
