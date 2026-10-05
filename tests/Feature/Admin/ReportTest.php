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
 * Laporan periode dari sisi admin (PRD section 29, ROADMAP 5.1).
 *
 * Waktu dibekukan pada satu tanggal supaya default "bulan berjalan" bisa
 * diuji dengan pasti. Semua timestamp data diisi eksplisit agar test tidak
 * bergantung pada jam berapa suite dijalankan.
 */
class ReportTest extends TestCase
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
        $this->get(route('admin.reports.index'))
            ->assertRedirect(route('login'));
    }

    public function test_the_default_period_is_the_current_month(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.reports.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/reports/index')
                ->where('filters.from', '2026-10-01')
                ->where('filters.to', '2026-10-15')
                ->where('report.period.from', '2026-10-01')
                ->where('report.period.to', '2026-10-15')
            );
    }

    public function test_invalid_dates_fall_back_to_the_default_period(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.reports.index', [
                'from' => '2026-02-31',
                'to' => 'bukan-tanggal',
            ]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('filters.from', '2026-10-01')
                ->where('filters.to', '2026-10-15')
            );
    }

    public function test_reversed_dates_are_swapped(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.reports.index', [
                'from' => '2026-10-20',
                'to' => '2026-10-05',
            ]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('filters.from', '2026-10-05')
                ->where('filters.to', '2026-10-20')
            );
    }

    public function test_it_counts_bookings_created_inside_the_period(): void
    {
        $user = User::factory()->create();
        $this->booking($user->business, '2026-10-03 09:00:00');
        $this->booking($user->business, '2026-10-14 09:00:00');
        $this->booking($user->business, '2026-09-30 09:00:00');

        $this->actingAs($user)
            ->get(route('admin.reports.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('report.stats.bookings', 2)
            );
    }

    public function test_finished_and_cancelled_bookings_use_their_own_timestamps(): void
    {
        $user = User::factory()->create();

        // Dibuat bulan lalu, selesai bulan ini: dihitung sebagai selesai.
        $this->booking(
            $user->business,
            '2026-09-20 09:00:00',
            status: BookingStatus::Selesai,
            completedAt: '2026-10-04 10:00:00',
        );

        // Selesai di luar periode: tidak dihitung selesai.
        $this->booking(
            $user->business,
            '2026-10-06 09:00:00',
            status: BookingStatus::Selesai,
            completedAt: '2026-10-20 10:00:00',
        );

        $this->booking(
            $user->business,
            '2026-10-07 09:00:00',
            status: BookingStatus::Dibatalkan,
            cancelledAt: '2026-10-08 10:00:00',
        );

        $this->actingAs($user)
            ->get(route('admin.reports.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('report.stats.bookings', 2)
                ->where('report.stats.finished', 1)
                ->where('report.stats.cancelled', 1)
            );
    }

    public function test_it_counts_distinct_customers(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $other = Customer::factory()->create();

        $this->booking($user->business, '2026-10-03 09:00:00', customer: $customer);
        $this->booking($user->business, '2026-10-04 09:00:00', customer: $customer);
        $this->booking($user->business, '2026-10-05 09:00:00', customer: $other);

        $this->actingAs($user)
            ->get(route('admin.reports.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('report.stats.customers', 2)
            );
    }

    public function test_revenue_counts_only_payments_verified_inside_the_period(): void
    {
        $user = User::factory()->create();

        $this->payment($user->business, amount: 100000, paidAt: '2026-10-05 10:00:00');
        $this->payment($user->business, amount: 50000, paidAt: '2026-09-25 10:00:00');
        $this->payment(
            $user->business,
            amount: 75000,
            status: PaymentStatus::MenungguVerifikasi,
        );

        $this->actingAs($user)
            ->get(route('admin.reports.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('report.stats.revenue', 100000)
                ->where('report.stats.revenue_label', '100.000')
            );
    }

    public function test_top_products_exclude_cancelled_bookings_and_use_the_snapshot_name(): void
    {
        $user = User::factory()->create();

        $this->bookingWithItem($user->business, 'Tenda Dome', quantity: 2, subtotal: 200000);
        $this->bookingWithItem($user->business, 'Tenda Dome', quantity: 1, subtotal: 100000);
        $this->bookingWithItem(
            $user->business,
            'Kursi Lipat',
            quantity: 10,
            subtotal: 500000,
            status: BookingStatus::Dibatalkan,
        );

        $this->actingAs($user)
            ->get(route('admin.reports.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('report.top_products', 1)
                ->where('report.top_products.0.product_name', 'Tenda Dome')
                ->where('report.top_products.0.quantity', 3)
                ->where('report.top_products.0.revenue', 300000)
            );
    }

    public function test_payment_method_recap_counts_transactions_and_paid_amounts(): void
    {
        $user = User::factory()->create();

        $this->payment($user->business, amount: 100000, method: PaymentMethodType::Cash, paidAt: '2026-10-05 10:00:00');
        $this->payment(
            $user->business,
            amount: 200000,
            method: PaymentMethodType::Qris,
            status: PaymentStatus::MenungguVerifikasi,
        );
        $this->payment(
            $user->business,
            amount: 300000,
            method: PaymentMethodType::BankTransfer,
            paidAt: '2026-09-25 10:00:00',
            createdAt: '2026-09-25 09:30:00',
        );

        $this->actingAs($user)
            ->get(route('admin.reports.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('report.payment_methods', 3)
                ->where('report.payment_methods.0.method', PaymentMethodType::Cash->value)
                ->where('report.payment_methods.0.transactions', 1)
                ->where('report.payment_methods.0.amount', 100000)
                ->where('report.payment_methods.0.paid', 100000)
                ->where('report.payment_methods.1.method', PaymentMethodType::Qris->value)
                ->where('report.payment_methods.1.transactions', 1)
                ->where('report.payment_methods.1.amount', 200000)
                ->where('report.payment_methods.1.paid', 0)
                ->where('report.payment_methods.2.method', PaymentMethodType::BankTransfer->value)
                ->where('report.payment_methods.2.transactions', 0)
            );
    }

    public function test_the_report_only_counts_data_from_the_current_business(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $foreign = $this->booking($other->business, '2026-10-03 09:00:00');
        $this->payment($other->business, amount: 500000, paidAt: '2026-10-05 10:00:00');
        BookingItem::factory()->create([
            'booking_id' => $foreign->getKey(),
            'product_name' => 'Tenda Unit Lain',
            'quantity' => 5,
            'subtotal' => 500000,
        ]);

        $this->actingAs($user)
            ->get(route('admin.reports.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('report.stats.bookings', 0)
                ->where('report.stats.revenue', 0)
                ->has('report.top_products', 0)
            );
    }

    public function test_a_short_period_is_charted_daily(): void
    {
        $user = User::factory()->create();
        $this->payment($user->business, amount: 50000, paidAt: '2026-10-02 10:00:00');

        $this->actingAs($user)
            ->get(route('admin.reports.index', [
                'from' => '2026-10-01',
                'to' => '2026-10-03',
            ]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('report.revenue_chart.granularity', 'harian')
                ->has('report.revenue_chart.points', 3)
                ->where('report.revenue_chart.points.0.amount', 0)
                ->where('report.revenue_chart.points.1.key', '2026-10-02')
                ->where('report.revenue_chart.points.1.amount', 50000)
                ->where('report.revenue_chart.total', 50000)
            );
    }

    public function test_a_long_period_is_charted_monthly(): void
    {
        $user = User::factory()->create();
        $this->payment($user->business, amount: 50000, paidAt: '2026-02-10 10:00:00');

        $this->actingAs($user)
            ->get(route('admin.reports.index', [
                'from' => '2026-01-01',
                'to' => '2026-03-31',
            ]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('report.revenue_chart.granularity', 'bulanan')
                ->has('report.revenue_chart.points', 3)
                ->where('report.revenue_chart.points.1.key', '2026-02')
                ->where('report.revenue_chart.points.1.amount', 50000)
                ->where('report.revenue_chart.total', 50000)
                ->where('report.stats.revenue', 50000)
            );
    }

    private function booking(
        Business $business,
        string $createdAt,
        BookingStatus $status = BookingStatus::MenungguKonfirmasi,
        ?Customer $customer = null,
        ?string $completedAt = null,
        ?string $cancelledAt = null,
    ): Booking {
        return Booking::factory()->forBusiness($business)->create([
            'customer_id' => ($customer ?? Customer::factory()->create())->getKey(),
            'booking_status' => $status,
            'completed_at' => $completedAt,
            'cancelled_at' => $cancelledAt,
            'created_at' => $createdAt,
        ]);
    }

    private function bookingWithItem(
        Business $business,
        string $productName,
        int $quantity,
        int $subtotal,
        BookingStatus $status = BookingStatus::Selesai,
    ): Booking {
        $booking = $this->booking(
            $business,
            '2026-10-05 09:00:00',
            status: $status,
            completedAt: $status === BookingStatus::Selesai ? '2026-10-06 09:00:00' : null,
        );

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
        ?string $paidAt = null,
        string $createdAt = '2026-10-01 09:30:00',
    ): Payment {
        $booking = $this->booking($business, '2026-10-01 09:00:00');

        return Payment::factory()->create([
            'booking_id' => $booking->getKey(),
            'business_id' => $business->getKey(),
            'method' => $method,
            'amount' => $amount,
            'status' => $status,
            'verified_at' => $paidAt,
            'created_at' => $createdAt,
        ]);
    }
}
