<?php

namespace Tests\Feature\Admin;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethodType;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Business;
use App\Models\Category;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Support\BookingPeriod;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Nama penyewa dari booking terakhir yang dibuat helper, dipakai untuk
     * memastikan widget booking terbaru bukan cuma soal jumlahnya.
     */
    private string $customerName = '';

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_admin_can_visit_the_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/dashboard')
                ->where('business.name', $user->business->name)
                ->where('business.slug', $user->business->slug)
                ->where('business.bookingCodePrefix', $user->business->booking_code_prefix)
                ->has('stats')
            );
    }

    public function test_dashboard_stats_only_count_the_owning_business(): void
    {
        $business = Business::factory()->create();
        $otherBusiness = Business::factory()->create();

        $category = Category::factory()->forBusiness($business)->create();
        $otherCategory = Category::factory()->forBusiness($otherBusiness)->create();

        Product::factory()
            ->forBusiness($business)
            ->withCategory($category)
            ->create();

        Product::factory()
            ->forBusiness($otherBusiness)
            ->withCategory($otherCategory)
            ->create();

        // Produk nonaktif tidak boleh dihitung.
        Product::factory()->forBusiness($business)->inactive()->create();

        $booking = Booking::factory()
            ->forBusiness($business)
            ->state(['booking_status' => BookingStatus::SedangDisewa])
            ->create();

        Booking::factory()
            ->forBusiness($otherBusiness)
            ->state(['booking_status' => BookingStatus::SedangDisewa])
            ->create();

        Payment::factory()
            ->forBusiness($business)
            ->state([
                'booking_id' => $booking->id,
                'method' => PaymentMethodType::Qris,
                'status' => PaymentStatus::MenungguVerifikasi,
            ])
            ->create();

        Payment::factory()
            ->forBusiness($otherBusiness)
            ->state([
                'method' => PaymentMethodType::Qris,
                'status' => PaymentStatus::MenungguVerifikasi,
            ])
            ->create();

        $this->actingAs(User::factory()->forBusiness($business)->create())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('stats.totalProducts', 1)
                ->where('stats.bookingsToday', 1)
                ->where('stats.rented', 1)
                ->where('stats.awaitingConfirmation', 0)
                ->where('stats.pendingPayments', 1)
            );
    }

    public function test_dashboard_uses_the_business_of_the_logged_in_admin(): void
    {
        $business = Business::factory()->create();
        $otherBusiness = Business::factory()->create();

        $this->actingAs(User::factory()->forBusiness($business)->create())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('business.slug', $business->slug)
                ->where('business.name', $business->name)
                ->etc()
            );

        $this->actingAs(User::factory()->forBusiness($otherBusiness)->create())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('business.slug', $otherBusiness->slug)
                ->where('business.name', $otherBusiness->name)
                ->etc()
            );
    }

    public function test_pendapatan_hanya_menghitung_pembayaran_lunas_bulan_ini(): void
    {
        $business = Business::factory()->create();

        $this->paidPayment($business, 300000, now());
        $this->paidPayment($business, 150000, now()->startOfMonth()->addDay());

        // Pembayaran lunas bulan lalu tidak masuk periode berjalan.
        $this->paidPayment($business, 999000, now()->subMonth()->addDay());

        // Sudah lunas tapi belum pernah dikonfirmasi admin tidak dihitung.
        Payment::factory()->forBusiness($business)->create([
            'amount' => 500000,
            'status' => PaymentStatus::Lunas,
            'verified_at' => null,
        ]);

        $this->actingAs(User::factory()->forBusiness($business)->create())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('stats.revenue.paid', 450000)
                ->where('stats.revenue.paid_label', '450.000')
                ->where('stats.revenue.period_label',
                    BookingPeriod::readableDate(now()->startOfMonth())
                        .' - '.BookingPeriod::readableDate(now()->endOfMonth()))
            );
    }

    public function test_pembayaran_yang_belum_lunas_dipisahkan_dari_pendapatan(): void
    {
        $business = Business::factory()->create();

        Payment::factory()->forBusiness($business)->create([
            'amount' => 200000,
            'status' => PaymentStatus::BelumDibayar,
        ]);

        Payment::factory()->forBusiness($business)->create([
            'amount' => 100000,
            'status' => PaymentStatus::MenungguVerifikasi,
        ]);

        // Ditolak bukan lagi menjadi hutang yang menunggu.
        Payment::factory()->forBusiness($business)->rejected()->create([
            'amount' => 75000,
        ]);

        $this->actingAs(User::factory()->forBusiness($business)->create())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('stats.revenue.paid', 0)
                ->where('stats.revenue.pending', 300000)
                ->where('stats.revenue.pending_label', '300.000')
            );
    }

    public function test_pendapatan_mengabaikan_pembayaran_unit_lain(): void
    {
        $business = Business::factory()->create();
        $otherBusiness = Business::factory()->create();

        $this->paidPayment($business, 100000, now());
        $this->paidPayment($otherBusiness, 750000, now());

        $this->actingAs(User::factory()->forBusiness($business)->create())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('stats.revenue.paid', 100000)
            );
    }

    public function test_grafik_booking_menutup_tujuh_hari_lengkap(): void
    {
        $business = Business::factory()->create();

        $this->bookingCreatedAt($business, now());
        $this->bookingCreatedAt($business, now());
        $this->bookingCreatedAt($business, now()->subDays(3));
        $this->bookingCreatedAt($business, now()->subDays(6));

        // Di luar jendela tujuh hari.
        $this->bookingCreatedAt($business, now()->subDays(8));

        $this->actingAs(User::factory()->forBusiness($business)->create())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('stats.bookingChart', 7)
                ->where('stats.bookingChart.0.date', today()->subDays(6)->toDateString())
                ->where('stats.bookingChart.0.count', 1)
                ->where('stats.bookingChart.1.count', 0)
                ->where('stats.bookingChart.3.count', 1)
                ->where('stats.bookingChart.6.date', today()->toDateString())
                ->where('stats.bookingChart.6.count', 2)
                ->where('stats.bookingChart.6.label',
                    BookingPeriod::readableShortDate(today()))
            );
    }

    public function test_grafik_booking_menghitung_tanggal_dibuat_bukan_tanggal_mulai_sewa(): void
    {
        $business = Business::factory()->create();

        $booking = $this->bookingCreatedAt($business, now()->subDay());

        $this->assertNotSame(
            today()->toDateString(),
            $booking->start_date->toDateString(),
            'Booking harus punya tanggal mulai yang beda dari hari dibuat.',
        );

        $this->actingAs(User::factory()->forBusiness($business)->create())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('stats.bookingChart.5.count', 1)
                ->where('stats.bookingChart.6.count', 0)
            );
    }

    public function test_grafik_booking_mengabaikan_unit_lain(): void
    {
        $business = Business::factory()->create();
        $otherBusiness = Business::factory()->create();

        $this->bookingCreatedAt($business, today());
        $this->bookingCreatedAt($otherBusiness, today());
        $this->bookingCreatedAt($otherBusiness, today());

        $this->actingAs(User::factory()->forBusiness($business)->create())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('stats.bookingChart.6.count', 1)
            );
    }

    public function test_booking_terbaru_dibatasi_lima_dan_berisi_ringkasan(): void
    {
        $business = Business::factory()->create();
        $otherBusiness = Business::factory()->create();

        for ($i = 1; $i <= 6; $i++) {
            $this->bookingCreatedAt($business, now()->subDays(7 - $i));
        }

        $newestCustomer = $this->customerName;

        $this->bookingCreatedAt($otherBusiness, now());

        $period = today()->addDays(30);

        $this->actingAs(User::factory()->forBusiness($business)->create())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('stats.recentBookings', 5)
                ->where('stats.recentBookings.0.status',
                    BookingStatus::MenungguKonfirmasi->value)
                ->where('stats.recentBookings.0.status_label',
                    BookingStatus::MenungguKonfirmasi->label())
                ->where('stats.recentBookings.0.total_label', '300.000')
                ->where('stats.recentBookings.0.quantity', 2)
                ->where('stats.recentBookings.0.product_name', 'Tenda Dome 4 Person')
                ->where('stats.recentBookings.0.period_label',
                    BookingPeriod::readableShortDate($period).' - '.BookingPeriod::readableShortDate($period->copy()->addDays(2)))
                ->where('stats.recentBookings.0.customer_name', $newestCustomer)
            );
    }

    public function test_booking_terbaru_menyimpan_nama_produk_dari_item(): void
    {
        $business = Business::factory()->create();
        $product = Product::factory()->forBusiness($business)->create([
            'name' => 'Tenda Dome 4 Person',
        ]);

        $booking = Booking::factory()->forBusiness($business)->create();

        BookingItem::factory()->create([
            'booking_id' => $booking->getKey(),
            'product_id' => $product->getKey(),
            'product_name' => 'Tenda Dome 4 Person',
            'quantity' => 3,
        ]);

        $product->update(['name' => 'Tenda Baru']);

        $this->actingAs(User::factory()->forBusiness($business)->create())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('stats.recentBookings.0.product_name', 'Tenda Dome 4 Person')
                ->where('stats.recentBookings.0.quantity', 3)
            );
    }

    public function test_booking_terbaru_kosong_pada_unit_yang_baru(): void
    {
        $business = Business::factory()->create();

        $this->actingAs(User::factory()->forBusiness($business)->create())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('stats.recentBookings', 0)
                ->where('stats.revenue.paid', 0)
                ->has('stats.bookingChart', 7)
            );
    }

    private function paidPayment(Business $business, int $amount, CarbonInterface $verifiedAt): void
    {
        Payment::factory()->forBusiness($business)->create([
            'amount' => $amount,
            'method' => PaymentMethodType::Qris,
            'status' => PaymentStatus::Lunas,
            'verified_at' => $verifiedAt,
        ]);
    }

    /**
     * Booking yang dibuat pada tanggal tertentu, dengan periode sewa yang
     * sengaja dibuat berbeda dari tanggal dibuat, supaya tidak tertukar antara
     * "tanggal booking masuk" dan "tanggal mulai sewa".
     */
    private function bookingCreatedAt(Business $business, CarbonInterface $createdAt): Booking
    {
        $this->customerName = 'Zaki '.fake()->unique()->numerify('##');

        $booking = Booking::factory()
            ->forBusiness($business)
            ->forPeriod(
                $business,
                today()->addDays(30)->toDateString(),
                today()->addDays(32)->toDateString(),
                BookingStatus::MenungguKonfirmasi,
            )
            ->create([
                'created_at' => $createdAt,
                'subtotal' => 300000,
                'total' => 300000,
            ]);

        $booking->customer->update(['name' => $this->customerName]);

        BookingItem::factory()->create([
            'booking_id' => $booking->getKey(),
            'product_id' => Product::factory()->forBusiness($business)->create([
                'name' => 'Tenda Dome 4 Person',
            ])->getKey(),
            'product_name' => 'Tenda Dome 4 Person',
            'quantity' => 2,
        ]);

        return $booking;
    }
}
