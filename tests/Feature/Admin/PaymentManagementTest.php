<?php

namespace Tests\Feature\Admin;

use App\Enums\PaymentMethodType;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Manajemen pembayaran dari sisi admin (PRD section 26, ROADMAP 4.6).
 *
 * Setiap test memeriksa dampaknya pada dua tempat sekaligus: baris `payments`
 * yang berubah, dan salinan `bookings.payment_status` yang dipakai daftar
 * booking serta dashboard. Dua nilai itu tidak boleh berbeda.
 */
class PaymentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get(route('admin.payments.index'))
            ->assertRedirect(route('login'));
    }

    public function test_the_list_shows_payments_with_the_booking_and_customer(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create(['name' => 'Andi Nugroho']);
        $booking = Booking::factory()->forBusiness($user->business)->create([
            'customer_id' => $customer->getKey(),
        ]);
        $payment = Payment::factory()->qris()->create([
            'booking_id' => $booking->getKey(),
            'business_id' => $user->business->getKey(),
        ]);

        $this->actingAs($user)
            ->get(route('admin.payments.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/payments/index')
                ->has('payments.data', 1)
                ->where('payments.data.0.id', $payment->getKey())
                ->where('payments.data.0.booking_code', $booking->booking_code)
                ->where('payments.data.0.customer_name', 'Andi Nugroho')
                ->where('payments.data.0.can_verify', true)
                ->where('payments.data.0.can_reject', true)
            );
    }

    public function test_the_list_only_shows_payments_from_the_same_business(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->payment($user->business);
        $foreign = $this->payment($other->business);

        $this->actingAs($user)
            ->get(route('admin.payments.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('payments.data', 1)
                ->where('payments.data.0.id', fn (int $id): bool => $id !== $foreign->getKey())
            );
    }

    public function test_a_payment_from_another_business_cannot_be_verified(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $foreign = $this->payment($other->business);

        $this->actingAs($user)
            ->patch(route('admin.payments.verify', $foreign))
            ->assertNotFound();

        $this->assertSame(
            PaymentStatus::MenungguVerifikasi,
            $foreign->refresh()->status,
        );
    }

    public function test_the_list_can_be_filtered_by_status(): void
    {
        $user = User::factory()->create();
        $waiting = $this->payment($user->business);
        $this->payment(
            $user->business,
            status: PaymentStatus::Lunas,
        );

        $this->actingAs($user)
            ->get(route('admin.payments.index', ['status' => PaymentStatus::MenungguVerifikasi->value]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('payments.data', 1)
                ->where('payments.data.0.id', $waiting->getKey())
            );
    }

    public function test_the_list_can_be_filtered_by_method(): void
    {
        $user = User::factory()->create();
        $cash = $this->payment(
            $user->business,
            method: PaymentMethodType::Cash,
            status: PaymentStatus::BelumDibayar,
            proof: null,
        );
        $this->payment($user->business);

        $this->actingAs($user)
            ->get(route('admin.payments.index', ['method' => PaymentMethodType::Cash->value]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('payments.data', 1)
                ->where('payments.data.0.id', $cash->getKey())
            );
    }

    public function test_invalid_filters_are_ignored(): void
    {
        $user = User::factory()->create();
        $this->payment($user->business);

        $this->actingAs($user)
            ->get(route('admin.payments.index', [
                'status' => 'entah',
                'method' => 'entah',
            ]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('payments.data', 1)
                ->where('filters.status', null)
                ->where('filters.method', null)
            );
    }

    public function test_admin_can_verify_a_waiting_payment(): void
    {
        $user = User::factory()->create();
        $payment = $this->payment($user->business);

        $this->actingAs($user)
            ->patch(route('admin.payments.verify', $payment))
            ->assertRedirect();

        $payment->refresh();

        $this->assertSame(PaymentStatus::Lunas, $payment->status);
        $this->assertNull($payment->rejection_reason);
        $this->assertNotNull($payment->verified_at);
        $this->assertSame($user->getKey(), $payment->verified_by);
        $this->assertSame(PaymentStatus::Lunas, $payment->booking->payment_status);
    }

    /**
     * Cash tidak melewati tahap verifikasi bukti, jadi uang yang diterima di
     * lokasi langsung ditandai lunas dari `belum_dibayar`.
     */
    public function test_admin_can_mark_a_cash_payment_as_paid_directly(): void
    {
        $user = User::factory()->create();
        $payment = $this->payment(
            $user->business,
            method: PaymentMethodType::Cash,
            status: PaymentStatus::BelumDibayar,
            proof: null,
        );

        $this->actingAs($user)
            ->patch(route('admin.payments.verify', $payment))
            ->assertRedirect();

        $payment->refresh();

        $this->assertSame(PaymentStatus::Lunas, $payment->status);
        $this->assertNotNull($payment->verified_at);
        $this->assertSame($user->getKey(), $payment->verified_by);
        $this->assertSame(PaymentStatus::Lunas, $payment->booking->payment_status);
    }

    public function test_a_paid_payment_cannot_be_verified_again(): void
    {
        $user = User::factory()->create();
        $payment = $this->payment(
            $user->business,
            status: PaymentStatus::Lunas,
        );

        $this->actingAs($user)
            ->patch(route('admin.payments.verify', $payment))
            ->assertSessionHasErrors('status');

        $this->assertSame(PaymentStatus::Lunas, $payment->refresh()->status);
    }

    public function test_a_rejected_payment_cannot_be_verified(): void
    {
        $user = User::factory()->create();
        $payment = $this->payment(
            $user->business,
            status: PaymentStatus::Ditolak,
            reason: 'Nominal tidak sesuai',
        );

        $this->actingAs($user)
            ->patch(route('admin.payments.verify', $payment))
            ->assertSessionHasErrors('status');

        $this->assertSame(PaymentStatus::Ditolak, $payment->refresh()->status);
    }

    public function test_admin_can_reject_a_waiting_payment_with_a_reason(): void
    {
        $user = User::factory()->create();
        $payment = $this->payment($user->business);

        $this->actingAs($user)
            ->patch(route('admin.payments.reject', $payment), [
                'rejection_reason' => 'Nominal transfer tidak sesuai',
            ])
            ->assertRedirect();

        $payment->refresh();

        $this->assertSame(PaymentStatus::Ditolak, $payment->status);
        $this->assertSame('Nominal transfer tidak sesuai', $payment->rejection_reason);
        $this->assertNotNull($payment->verified_at);
        $this->assertSame($user->getKey(), $payment->verified_by);
        $this->assertSame(PaymentStatus::Ditolak, $payment->booking->payment_status);
    }

    public function test_rejecting_a_payment_requires_a_reason(): void
    {
        $user = User::factory()->create();
        $payment = $this->payment($user->business);

        $this->actingAs($user)
            ->patch(route('admin.payments.reject', $payment), [
                'rejection_reason' => '   ',
            ])
            ->assertSessionHasErrors('rejection_reason');

        $this->assertSame(PaymentStatus::MenungguVerifikasi, $payment->refresh()->status);
    }

    public function test_the_rejection_reason_is_limited_to_the_column_width(): void
    {
        $user = User::factory()->create();
        $payment = $this->payment($user->business);

        $this->actingAs($user)
            ->patch(route('admin.payments.reject', $payment), [
                'rejection_reason' => str_repeat('a', 256),
            ])
            ->assertSessionHasErrors('rejection_reason');

        $this->assertSame(PaymentStatus::MenungguVerifikasi, $payment->refresh()->status);
    }

    public function test_a_cash_payment_that_is_not_paid_yet_cannot_be_rejected(): void
    {
        $user = User::factory()->create();
        $payment = $this->payment(
            $user->business,
            method: PaymentMethodType::Cash,
            status: PaymentStatus::BelumDibayar,
            proof: null,
        );

        $this->actingAs($user)
            ->patch(route('admin.payments.reject', $payment), [
                'rejection_reason' => 'Tidak jadi bayar',
            ])
            ->assertSessionHasErrors('rejection_reason');

        $this->assertSame(PaymentStatus::BelumDibayar, $payment->refresh()->status);
    }

    public function test_a_paid_payment_cannot_be_rejected(): void
    {
        $user = User::factory()->create();
        $payment = $this->payment(
            $user->business,
            status: PaymentStatus::Lunas,
        );

        $this->actingAs($user)
            ->patch(route('admin.payments.reject', $payment), [
                'rejection_reason' => 'Salah transfer',
            ])
            ->assertSessionHasErrors('rejection_reason');

        $this->assertSame(PaymentStatus::Lunas, $payment->refresh()->status);
    }

    /**
     * Pembayaran unit bisnis ini, dengan booking dan status yang bisa diatur
     * supaya test mudah dibaca.
     */
    private function payment(
        Business $business,
        PaymentStatus $status = PaymentStatus::MenungguVerifikasi,
        PaymentMethodType $method = PaymentMethodType::Qris,
        ?string $proof = 'payments/bukti.jpg',
        ?string $reason = null,
    ): Payment {
        $booking = Booking::factory()->forBusiness($business)->create([
            'payment_method' => $method,
            'payment_status' => $status,
        ]);

        return Payment::factory()->create([
            'booking_id' => $booking->getKey(),
            'business_id' => $business->getKey(),
            'method' => $method,
            'amount' => 150000,
            'proof' => $proof,
            'status' => $status,
            'rejection_reason' => $reason,
        ]);
    }
}
