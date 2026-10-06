<?php

namespace Tests\Feature\Admin;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cek tiket dari panel admin (ROADMAP 5.5).
 *
 * Berbeda dari section publik yang lintas unit, panel admin terbatas pada unit
 * admin yang login. Test ini memastikan isolasi itu dan memastikan payload
 * tiket tidak membawa data pribadi yang tidak perlu.
 */
class TicketCheckAdminTest extends TestCase
{
    use RefreshDatabase;

    private const WHATSAPP = '628123456789';

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get(route('admin.tickets.index'))
            ->assertRedirect(route('login'));
    }

    public function test_the_page_shows_the_form_without_a_ticket(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.tickets.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/tickets/index')
                ->where('ticket', null)
            );
    }

    public function test_admin_finds_a_ticket_of_their_own_unit(): void
    {
        $user = User::factory()->create();
        $booking = $this->booking($user->business);

        $this->actingAs($user)
            ->post(route('admin.tickets.lookup'), [
                'booking_code' => $booking->booking_code,
                'whatsapp' => self::WHATSAPP,
            ])
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/tickets/index')
                ->where('ticket.booking_code', $booking->booking_code)
                ->where('ticket.customer_name', 'Budi Santoso')
                ->where('ticket.status_label', 'Menunggu Konfirmasi')
                ->where('ticket.total_label', '150.000')
                ->has('ticket.items', 1)
                ->missing('ticket.nik')
                ->missing('ticket.address')
            );
    }

    public function test_admin_cannot_find_a_ticket_from_another_unit(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $foreign = $this->booking($other->business);

        $this->actingAs($user)
            ->from(route('admin.tickets.index'))
            ->post(route('admin.tickets.lookup'), [
                'booking_code' => $foreign->booking_code,
                'whatsapp' => self::WHATSAPP,
            ])
            ->assertRedirect(route('admin.tickets.index'))
            ->assertSessionHasErrors('booking_code');
    }

    public function test_admin_must_provide_the_customer_number_to_open_a_ticket(): void
    {
        $user = User::factory()->create();
        $booking = $this->booking($user->business);

        $this->actingAs($user)
            ->from(route('admin.tickets.index'))
            ->post(route('admin.tickets.lookup'), [
                'booking_code' => $booking->booking_code,
                'whatsapp' => '628999999999',
            ])
            ->assertSessionHasErrors('booking_code');
    }

    public function test_an_invalid_whatsapp_number_is_rejected(): void
    {
        $user = User::factory()->create();
        $booking = $this->booking($user->business);

        $this->actingAs($user)
            ->from(route('admin.tickets.index'))
            ->post(route('admin.tickets.lookup'), [
                'booking_code' => $booking->booking_code,
                'whatsapp' => 'bukan-nomor',
            ])
            ->assertSessionHasErrors('whatsapp');
    }

    public function test_a_cancelled_ticket_shows_the_reason(): void
    {
        $user = User::factory()->create();
        $booking = $this->booking(
            $user->business,
            status: BookingStatus::Dibatalkan,
            cancellationReason: 'Penyewa tidak muncul di lokasi',
        );

        $this->actingAs($user)
            ->post(route('admin.tickets.lookup'), [
                'booking_code' => $booking->booking_code,
                'whatsapp' => self::WHATSAPP,
            ])
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('ticket.cancellation_reason', 'Penyewa tidak muncul di lokasi')
            );
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
