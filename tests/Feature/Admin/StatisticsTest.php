<?php

namespace Tests\Feature\Admin;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethodType;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Statistik tahunan admin (ROADMAP 5.3).
 *
 * Waktu dibekukan pada Oktober 2026 supaya tahun default dan label bulan bisa
 * diuji dengan pasti.
 */
class StatisticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-15 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get(route('admin.statistics.index'))
            ->assertRedirect(route('login'));
    }

    public function test_the_default_year_is_the_current_one(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.statistics.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/statistics/index')
                ->where('year', 2026)
                ->where('years', [2026])
                ->has('booking_chart.points', 12)
                ->has('revenue_chart.points', 12)
                ->where('booking_chart.points.0.key', '2026-01')
                ->where('booking_chart.points.11.key', '2026-12')
            );
    }

    public function test_the_year_filter_is_respected_and_invalid_values_fall_back(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.statistics.index', ['year' => '2025']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('year', 2025)
                ->where('booking_chart.points.0.key', '2025-01')
            );

        $this->actingAs($user)
            ->get(route('admin.statistics.index', ['year' => 'entah']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('year', 2026));
    }

    public function test_the_booking_chart_counts_bookings_per_month(): void
    {
        $user = User::factory()->create();
        $this->booking($user->business, '2026-01-10 09:00:00');
        $this->booking($user->business, '2026-01-20 09:00:00');
        $this->booking($user->business, '2026-03-05 09:00:00');
        $this->booking($user->business, '2025-01-05 09:00:00');

        $this->actingAs($user)
            ->get(route('admin.statistics.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('booking_chart.points.0.key', '2026-01')
                ->where('booking_chart.points.0.value', 2)
                ->where('booking_chart.points.2.key', '2026-03')
                ->where('booking_chart.points.2.value', 1)
                ->where('booking_chart.points.1.value', 0)
            );
    }

    public function test_the_revenue_chart_counts_paid_payments_per_month(): void
    {
        $user = User::factory()->create();
        $this->payment($user->business, 100000, paidAt: '2026-02-10 10:00:00');
        $this->payment($user->business, 50000, paidAt: '2026-02-20 10:00:00');
        $this->payment($user->business, 70000, paidAt: '2025-02-10 10:00:00');
        $this->payment(
            $user->business,
            90000,
            status: PaymentStatus::MenungguVerifikasi,
            paidAt: null,
        );

        $this->actingAs($user)
            ->get(route('admin.statistics.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('revenue_chart.points.1.key', '2026-02')
                ->where('revenue_chart.points.1.value', 150000)
                ->where('revenue_chart.points.0.value', 0)
                ->where('revenue_chart.total', 150000)
            );
    }

    public function test_top_products_exclude_cancelled_and_other_years(): void
    {
        $user = User::factory()->create();
        $this->bookingWithItem($user->business, '2026-01-10 09:00:00', 'Tenda Dome', 2, 200000);
        $this->bookingWithItem($user->business, '2026-02-10 09:00:00', 'Tenda Dome', 1, 100000);
        $this->bookingWithItem($user->business, '2026-03-10 09:00:00', 'Kursi Lipat', 5, 250000, BookingStatus::Dibatalkan);
        $this->bookingWithItem($user->business, '2025-01-10 09:00:00', 'Tas Carrier', 9, 900000);

        $this->actingAs($user)
            ->get(route('admin.statistics.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('top_products', 1)
                ->where('top_products.0.product_name', 'Tenda Dome')
                ->where('top_products.0.quantity', 3)
            );
    }

    public function test_payment_methods_are_ordered_by_usage(): void
    {
        $user = User::factory()->create();
        $this->payment($user->business, 100000, method: PaymentMethodType::Cash, paidAt: '2026-01-10 10:00:00');
        $this->payment($user->business, 200000, method: PaymentMethodType::Qris, paidAt: '2026-02-10 10:00:00');
        $this->payment($user->business, 300000, method: PaymentMethodType::Qris, paidAt: '2026-03-10 10:00:00');

        $this->actingAs($user)
            ->get(route('admin.statistics.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('payment_methods', 3)
                ->where('payment_methods.0.method', PaymentMethodType::Qris->value)
                ->where('payment_methods.0.transactions', 2)
                ->where('payment_methods.0.paid', 500000)
                ->where('payment_methods.1.method', PaymentMethodType::Cash->value)
                ->where('payment_methods.1.transactions', 1)
                ->where('payment_methods.2.method', PaymentMethodType::BankTransfer->value)
                ->where('payment_methods.2.transactions', 0)
            );
    }

    public function test_the_year_selector_lists_years_with_data_and_the_current_year(): void
    {
        $user = User::factory()->create();
        $this->booking($user->business, '2024-05-05 09:00:00');
        $this->payment($user->business, 100000, paidAt: '2025-06-10 10:00:00');

        $this->actingAs($user)
            ->get(route('admin.statistics.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('years', [2026, 2025, 2024])
            );
    }

    public function test_statistics_only_count_data_from_the_current_business(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $foreign = $this->booking($other->business, '2026-01-10 09:00:00');
        $this->payment($other->business, 500000, paidAt: '2026-02-10 10:00:00');
        BookingItem::factory()->create([
            'booking_id' => $foreign->getKey(),
            'product_name' => 'Tenda Unit Lain',
            'quantity' => 5,
            'subtotal' => 500000,
        ]);

        $this->actingAs($user)
            ->get(route('admin.statistics.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('booking_chart.points.0.value', 0)
                ->where('revenue_chart.total', 0)
                ->has('top_products', 0)
                ->where('payment_methods.0.transactions', 0)
            );
    }

    private function booking(Business $business, string $createdAt): Booking
    {
        return Booking::factory()->forBusiness($business)->create([
            'customer_id' => Customer::factory()->create()->getKey(),
            'created_at' => $createdAt,
        ]);
    }

    private function bookingWithItem(
        Business $business,
        string $createdAt,
        string $productName,
        int $quantity,
        int $subtotal,
        BookingStatus $status = BookingStatus::Selesai,
    ): Booking {
        $booking = $this->booking($business, $createdAt);
        $booking->update(['booking_status' => $status]);

        BookingItem::factory()->create([
            'booking_id' => $booking->getKey(),
            'product_name' => $productName,
            'quantity' => $quantity,
            'subtotal' => $subtotal,
        ]);

        return $booking;
    }

    private function payment(
        Business $business,
        int $amount,
        PaymentStatus $status = PaymentStatus::Lunas,
        PaymentMethodType $method = PaymentMethodType::Cash,
        ?string $paidAt = '2026-01-10 10:00:00',
    ): Payment {
        return Payment::factory()->create([
            'booking_id' => $this->booking($business, '2026-01-01 09:00:00')->getKey(),
            'business_id' => $business->getKey(),
            'method' => $method,
            'amount' => $amount,
            'status' => $status,
            'verified_at' => $paidAt,
            'created_at' => '2026-01-01 09:30:00',
        ]);
    }
}
