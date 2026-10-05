<?php

namespace Tests\Feature\Admin;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethodType;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\BookingStatusHistory;
use App\Models\Business;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Services\AvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Manajemen booking dari sisi admin (PRD section 24, ROADMAP 4.4).
 *
 * Setiap test memakai admin dengan business-nya sendiri supaya pengujian isolasi
 * tenant tidak bergantung pada state dari test lain.
 */
class BookingManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get(route('admin.bookings.index'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_sees_the_booking_list(): void
    {
        $user = User::factory()->create();
        $booking = $this->booking($user->business);

        $this->actingAs($user)
            ->get(route('admin.bookings.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/bookings/index')
                ->has('bookings.data', 1)
                ->where('bookings.data.0.booking_code', $booking->booking_code)
            );
    }

    public function test_the_list_only_shows_bookings_from_the_same_business(): void
    {
        $user = User::factory()->create();
        $this->booking($user->business);

        $other = User::factory()->create();
        $foreign = $this->booking($other->business);

        $this->actingAs($user)
            ->get(route('admin.bookings.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/bookings/index')
                ->has('bookings.data', 1)
                ->where('bookings.data.0.booking_code', fn (string $code): bool => $code !== $foreign->booking_code)
            );
    }

    public function test_a_booking_from_another_business_is_not_found(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $foreign = $this->booking($other->business);

        $this->actingAs($user)
            ->get(route('admin.bookings.show', $foreign->booking_code))
            ->assertNotFound();
    }

    public function test_a_booking_from_another_business_cannot_change_status(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $foreign = $this->booking($other->business);

        $this->actingAs($user)
            ->patch(route('admin.bookings.status', $foreign->booking_code), [
                'status' => BookingStatus::Dikonfirmasi->value,
            ])
            ->assertNotFound();

        $this->assertSame(
            BookingStatus::MenungguKonfirmasi,
            $foreign->refresh()->booking_status,
        );
    }

    public function test_the_list_can_be_searched_by_booking_code(): void
    {
        $user = User::factory()->create();
        $wanted = $this->booking($user->business);
        $other = $this->booking($user->business);

        $this->actingAs($user)
            ->get(route('admin.bookings.index', ['q' => $wanted->booking_code]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('bookings.data', 1)
                ->where('bookings.data.0.booking_code', $wanted->booking_code)
            );

        $this->assertNotSame($wanted->booking_code, $other->booking_code);
    }

    public function test_the_list_can_be_searched_by_customer_name(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create(['name' => 'Zaki Pratama']);
        $booking = $this->booking($user->business, $customer);
        $this->booking($user->business);

        $this->actingAs($user)
            ->get(route('admin.bookings.index', ['q' => 'Zaki']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('bookings.data', 1)
                ->where('bookings.data.0.booking_code', $booking->booking_code)
            );
    }

    public function test_the_list_can_be_searched_by_customer_whatsapp(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->withWhatsapp('628123456789')->create();
        $booking = $this->booking($user->business, $customer);
        $this->booking($user->business);

        $this->actingAs($user)
            ->get(route('admin.bookings.index', ['q' => '628123456789']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('bookings.data', 1)
                ->where('bookings.data.0.booking_code', $booking->booking_code)
            );
    }

    public function test_the_list_can_be_searched_by_product_name_snapshot(): void
    {
        $user = User::factory()->create();
        $booking = $this->booking($user->business, productName: 'Tenda Dome 4 Person');
        $this->booking($user->business, productName: 'Kursi Lipat');

        $this->actingAs($user)
            ->get(route('admin.bookings.index', ['q' => 'Tenda']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('bookings.data', 1)
                ->where('bookings.data.0.booking_code', $booking->booking_code)
            );
    }

    public function test_the_list_can_be_filtered_by_status(): void
    {
        $user = User::factory()->create();
        $confirmed = $this->booking($user->business, status: BookingStatus::Dikonfirmasi);
        $this->booking($user->business, status: BookingStatus::MenungguKonfirmasi);

        $this->actingAs($user)
            ->get(route('admin.bookings.index', ['status' => BookingStatus::Dikonfirmasi->value]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('bookings.data', 1)
                ->where('bookings.data.0.booking_code', $confirmed->booking_code)
            );
    }

    public function test_the_list_can_be_filtered_by_start_date_range(): void
    {
        $user = User::factory()->create();
        $october = $this->booking($user->business, startDate: '2026-10-10', endDate: '2026-10-12');
        $november = $this->booking($user->business, startDate: '2026-11-05', endDate: '2026-11-06');

        $this->actingAs($user)
            ->get(route('admin.bookings.index', [
                'from' => '2026-10-01',
                'to' => '2026-10-31',
            ]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('bookings.data', 1)
                ->where('bookings.data.0.booking_code', $october->booking_code)
            );

        $this->assertNotSame($october->booking_code, $november->booking_code);
    }

    public function test_the_date_filter_uses_start_date_not_the_created_at_date(): void
    {
        $user = User::factory()->create();
        $booking = $this->booking(
            $user->business,
            startDate: '2027-01-01',
            endDate: '2027-01-02',
        );

        // Booking dibuat hari ini untuk sewa bulan depan. Filter tanggal dibuat
        // hari ini harus tetap menemukannya lewat tanggal mulainya.
        $this->actingAs($user)
            ->get(route('admin.bookings.index', [
                'from' => now()->toDateString(),
                'to' => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('bookings.data', 0)
            );

        $this->actingAs($user)
            ->get(route('admin.bookings.index', [
                'from' => '2027-01-01',
                'to' => '2027-01-01',
            ]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('bookings.data', 1)
                ->where('bookings.data.0.booking_code', $booking->booking_code)
            );
    }

    public function test_invalid_filter_values_are_ignored(): void
    {
        $user = User::factory()->create();
        $booking = $this->booking($user->business);

        $this->actingAs($user)
            ->get(route('admin.bookings.index', [
                'status' => 'status-ngawur',
                'from' => 'kemarin',
                'to' => '2026-02-31',
            ]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('filters.status', null)
                ->where('filters.from', null)
                ->where('filters.to', null)
                ->has('bookings.data', 1)
                ->where('bookings.data.0.booking_code', $booking->booking_code)
            );
    }

    public function test_search_wildcards_are_treated_as_plain_text(): void
    {
        $user = User::factory()->create();
        $this->booking($user->business);

        $this->actingAs($user)
            ->get(route('admin.bookings.index', ['q' => '%']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('bookings.data', 0)
            );
    }

    public function test_the_booking_detail_shows_customer_items_and_payment(): void
    {
        $user = User::factory()->create();
        $booking = $this->booking(
            $user->business,
            customer: Customer::factory()->create(['name' => 'Andi Nugroho']),
            productName: 'Tenda Dome',
        );

        $this->actingAs($user)
            ->get(route('admin.bookings.show', $booking->booking_code))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/bookings/show')
                ->where('booking.booking_code', $booking->booking_code)
                ->where('booking.customer.name', 'Andi Nugroho')
                ->has('booking.items', 1)
                ->where('booking.items.0.product_name', 'Tenda Dome')
                ->where('booking.payment.status', PaymentStatus::BelumDibayar->value)
                ->where('booking.payment.method_label', 'Cash')
                ->has('history', 1)
            );
    }

    /**
     * Halaman detail hanya menampilkan satu tombol majukan status, yaitu tahap
     * berikutnya. Nilainya dikirim terpisah supaya frontend tidak pernah
     * menebak tahap sendiri dan tidak pernah menawarkan pilihan yang pasti
     * ditolak backend.
     */
    public function test_the_booking_detail_exposes_the_next_stage_and_the_cancellation_switch(): void
    {
        $user = User::factory()->create();
        $booking = $this->booking($user->business);

        $this->actingAs($user)
            ->get(route('admin.bookings.show', $booking->booking_code))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('booking.status', BookingStatus::MenungguKonfirmasi->value)
                ->where('booking.next_status', BookingStatus::Dikonfirmasi->value)
                ->where('booking.next_status_label', BookingStatus::Dikonfirmasi->label())
                ->where('booking.is_cancellable', true)
                ->where('booking.subtotal_label', '150.000')
                ->where('booking.total_label', '150.000')
            );
    }

    /**
     * Booking yang sudah selesai tidak punya tahap berikutnya dan tombol
     * pembatalannya hilang, jadi frontend tidak perlu menebak kapan harus
     * menyembunyikan aksi.
     */
    public function test_a_finished_booking_detail_has_no_next_stage_and_cannot_be_cancelled(): void
    {
        $user = User::factory()->create();
        $booking = $this->booking($user->business, status: BookingStatus::Selesai);

        $this->actingAs($user)
            ->get(route('admin.bookings.show', $booking->booking_code))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('booking.next_status', null)
                ->where('booking.next_status_label', null)
                ->where('booking.is_cancellable', false)
            );
    }

    public function test_the_booking_detail_masks_the_customer_nik(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create(['nik' => '3273011234560001']);
        $booking = $this->booking($user->business, $customer);

        $this->actingAs($user)
            ->get(route('admin.bookings.show', $booking->booking_code))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('booking.customer.nik', '**********560001')
                ->where('booking.customer.nik', fn (string $nik): bool => ! str_contains($nik, '3273011234560001'))
            );
    }

    public function test_admin_can_advance_the_status_one_step_at_a_time(): void
    {
        $user = User::factory()->create();
        $booking = $this->booking($user->business);

        $this->actingAs($user)
            ->patch(route('admin.bookings.status', $booking->booking_code), [
                'status' => BookingStatus::Dikonfirmasi->value,
            ])
            ->assertRedirect(route('admin.bookings.show', $booking->booking_code));

        $booking->refresh();

        $this->assertSame(BookingStatus::Dikonfirmasi, $booking->booking_status);
        $this->assertNotNull($booking->confirmed_at);
        $this->assertNull($booking->started_at);

        $this->actingAs($user)
            ->patch(route('admin.bookings.status', $booking->booking_code), [
                'status' => BookingStatus::SedangDisewa->value,
            ]);

        $booking->refresh();

        $this->assertSame(BookingStatus::SedangDisewa, $booking->booking_status);
        $this->assertNotNull($booking->started_at);

        $this->actingAs($user)
            ->patch(route('admin.bookings.status', $booking->booking_code), [
                'status' => BookingStatus::Selesai->value,
            ]);

        $booking->refresh();

        $this->assertSame(BookingStatus::Selesai, $booking->booking_status);
        $this->assertNotNull($booking->completed_at);
    }

    public function test_admin_cannot_skip_a_status_step(): void
    {
        $user = User::factory()->create();
        $booking = $this->booking($user->business);

        $this->actingAs($user)
            ->patch(route('admin.bookings.status', $booking->booking_code), [
                'status' => BookingStatus::Selesai->value,
            ])
            ->assertSessionHasErrors('status');

        $this->assertSame(
            BookingStatus::MenungguKonfirmasi,
            $booking->refresh()->booking_status,
        );
    }

    public function test_admin_cannot_move_a_status_backwards(): void
    {
        $user = User::factory()->create();
        $booking = $this->booking($user->business, status: BookingStatus::SedangDisewa);

        $this->actingAs($user)
            ->patch(route('admin.bookings.status', $booking->booking_code), [
                'status' => BookingStatus::Dikonfirmasi->value,
            ])
            ->assertSessionHasErrors('status');

        $this->assertSame(BookingStatus::SedangDisewa, $booking->refresh()->booking_status);
    }

    public function test_a_finished_booking_cannot_change_status(): void
    {
        $user = User::factory()->create();
        $booking = $this->booking($user->business, status: BookingStatus::Selesai);

        $this->actingAs($user)
            ->patch(route('admin.bookings.status', $booking->booking_code), [
                'status' => BookingStatus::Dibatalkan->value,
            ])
            ->assertSessionHasErrors('status');

        $this->assertSame(BookingStatus::Selesai, $booking->refresh()->booking_status);
    }

    public function test_every_status_change_is_recorded_in_the_history(): void
    {
        $user = User::factory()->create();
        $booking = $this->booking($user->business);

        $this->actingAs($user)
            ->patch(route('admin.bookings.status', $booking->booking_code), [
                'status' => BookingStatus::Dikonfirmasi->value,
            ]);

        $this->actingAs($user)
            ->get(route('admin.bookings.show', $booking->booking_code))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('history', 2)
                ->where('history.0.from_status', null)
                ->where('history.0.to_status', BookingStatus::MenungguKonfirmasi->value)
                ->where('history.1.from_status', BookingStatus::MenungguKonfirmasi->value)
                ->where('history.1.to_status', BookingStatus::Dikonfirmasi->value)
                ->where('history.1.author', $user->name)
            );
    }

    public function test_the_history_of_a_booking_created_by_a_customer_has_no_author(): void
    {
        $user = User::factory()->create();
        $booking = $this->booking($user->business);

        $this->actingAs($user)
            ->get(route('admin.bookings.show', $booking->booking_code))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('history', 1)
                ->where('history.0.to_status', BookingStatus::MenungguKonfirmasi->value)
                ->where('history.0.author', null)
            );
    }

    public function test_a_new_booking_gets_its_first_history_row(): void
    {
        $user = User::factory()->create();
        $booking = $this->booking($user->business);

        $this->assertSame(1, $booking->statusHistories()->count());
        $this->assertNull($booking->statusHistories()->first()?->from_status);
    }

    public function test_admin_can_cancel_a_booking_with_a_reason(): void
    {
        $user = User::factory()->create();
        $booking = $this->booking($user->business, status: BookingStatus::Dikonfirmasi);

        $this->actingAs($user)
            ->delete(route('admin.bookings.cancel', $booking->booking_code), [
                'cancellation_reason' => 'Penyewa tidak muncul di lokasi pengambilan',
            ])
            ->assertRedirect(route('admin.bookings.show', $booking->booking_code));

        $booking->refresh();

        $this->assertSame(BookingStatus::Dibatalkan, $booking->booking_status);
        $this->assertSame(
            'Penyewa tidak muncul di lokasi pengambilan',
            $booking->cancellation_reason,
        );
        $this->assertNotNull($booking->cancelled_at);

        $this->assertDatabaseHas('booking_status_histories', [
            'booking_id' => $booking->getKey(),
            'from_status' => BookingStatus::Dikonfirmasi->value,
            'to_status' => BookingStatus::Dibatalkan->value,
            'note' => 'Penyewa tidak muncul di lokasi pengambilan',
            'changed_by' => $user->getKey(),
        ]);
    }

    public function test_cancelling_a_booking_requires_a_reason(): void
    {
        $user = User::factory()->create();
        $booking = $this->booking($user->business, status: BookingStatus::Dikonfirmasi);

        $this->actingAs($user)
            ->delete(route('admin.bookings.cancel', $booking->booking_code), [
                'cancellation_reason' => '   ',
            ])
            ->assertSessionHasErrors('cancellation_reason');

        $this->assertSame(BookingStatus::Dikonfirmasi, $booking->refresh()->booking_status);
    }

    public function test_a_finished_booking_cannot_be_cancelled(): void
    {
        $user = User::factory()->create();
        $booking = $this->booking($user->business, status: BookingStatus::Selesai);

        $this->actingAs($user)
            ->delete(route('admin.bookings.cancel', $booking->booking_code), [
                'cancellation_reason' => 'Salah input',
            ])
            ->assertSessionHasErrors('cancellation_reason');

        $this->assertSame(BookingStatus::Selesai, $booking->refresh()->booking_status);
    }

    public function test_an_already_cancelled_booking_cannot_be_cancelled_again(): void
    {
        $user = User::factory()->create();
        $booking = $this->booking($user->business, status: BookingStatus::Dibatalkan);

        $this->actingAs($user)
            ->delete(route('admin.bookings.cancel', $booking->booking_code), [
                'cancellation_reason' => 'Batal lagi',
            ])
            ->assertSessionHasErrors('cancellation_reason');

        // Helper hanya menulis riwayat awal `menunggu_konfirmasi`, jadi penolakan
        // tidak boleh menambah baris riwayat baru maupun menimpa alasan lama.
        $this->assertSame(1, $booking->statusHistories()->count());
        $this->assertSame(
            0,
            $booking->statusHistories()
                ->where('to_status', BookingStatus::Dibatalkan->value)
                ->count(),
        );
        $this->assertNull($booking->refresh()->cancellation_reason);
    }

    public function test_cancelling_releases_the_booked_stock(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->forBusiness($user->business)->create(['stock' => 1]);
        $booking = $this->booking(
            $user->business,
            product: $product,
            status: BookingStatus::Dikonfirmasi,
        );

        $this->assertSame(0, $this->availableStock($product, $booking));

        $this->actingAs($user)
            ->delete(route('admin.bookings.cancel', $booking->booking_code), [
                'cancellation_reason' => 'Batal',
            ]);

        $this->assertSame(1, $this->availableStock($product->refresh(), $booking));
    }

    public function test_cancelling_closes_a_payment_that_is_still_waiting_for_verification(): void
    {
        $user = User::factory()->create();
        $booking = $this->booking(
            $user->business,
            status: BookingStatus::Dikonfirmasi,
            paymentStatus: PaymentStatus::MenungguVerifikasi,
            proof: 'payments/bukti.jpg',
        );

        $this->actingAs($user)
            ->delete(route('admin.bookings.cancel', $booking->booking_code), [
                'cancellation_reason' => 'Bukti tidak pernah masuk',
            ]);

        $payment = $booking->refresh()->payment;

        $this->assertSame(PaymentStatus::Ditolak, $payment?->status);
        $this->assertSame(
            'Booking '.$booking->booking_code.' dibatalkan: Bukti tidak pernah masuk',
            $payment?->rejection_reason,
        );
        $this->assertSame(PaymentStatus::Ditolak, $booking->payment_status);
    }

    public function test_cancelling_closes_an_unpaid_payment(): void
    {
        $user = User::factory()->create();
        $booking = $this->booking(
            $user->business,
            status: BookingStatus::Dikonfirmasi,
            paymentStatus: PaymentStatus::BelumDibayar,
        );

        $this->actingAs($user)
            ->delete(route('admin.bookings.cancel', $booking->booking_code), [
                'cancellation_reason' => 'Penyewa berubah pikiran',
            ]);

        $this->assertSame(PaymentStatus::Ditolak, $booking->refresh()->payment?->status);
    }

    public function test_cancelling_does_not_undo_a_verified_payment(): void
    {
        $user = User::factory()->create();
        $booking = $this->booking(
            $user->business,
            status: BookingStatus::SedangDisewa,
            paymentStatus: PaymentStatus::Lunas,
        );

        $this->actingAs($user)
            ->delete(route('admin.bookings.cancel', $booking->booking_code), [
                'cancellation_reason' => 'Refund dikembalikan manual',
            ]);

        $booking->refresh();

        // Uang sudah masuk, jadi status pembayaran bukan milik booking yang
        // dibatalkan. Membalik `lunas` jadi `ditolak` akan ikut mengubah angka
        // pendapatan dashboard, yang hanya menghitung pembayaran `lunas`.
        $this->assertSame(PaymentStatus::Lunas, $booking->payment?->status);
        $this->assertNull($booking->payment?->rejection_reason);
        $this->assertSame(PaymentStatus::Lunas, $booking->payment_status);
        $this->assertSame(BookingStatus::Dibatalkan, $booking->booking_status);
    }

    public function test_cancelling_keeps_the_payment_proof_on_disk(): void
    {
        $user = User::factory()->create();
        $booking = $this->booking(
            $user->business,
            status: BookingStatus::Dikonfirmasi,
            paymentStatus: PaymentStatus::MenungguVerifikasi,
            proof: 'payments/bukti.jpg',
        );

        $this->actingAs($user)
            ->delete(route('admin.bookings.cancel', $booking->booking_code), [
                'cancellation_reason' => 'Batal',
            ]);

        // Bukti pembayaran dihapus di Manajemen Pembayaran (ROADMAP 4.6) kalau
        // memang tidak perlu lagi. Pembatalan booking tidak menghapusnya,
        // supaya keputusan refund atau investigasi masih punya bahan.
        $this->assertSame('payments/bukti.jpg', $booking->refresh()->payment?->proof);
    }

    public function test_advancing_the_status_does_not_touch_the_payment(): void
    {
        $user = User::factory()->create();
        $booking = $this->booking(
            $user->business,
            paymentStatus: PaymentStatus::MenungguVerifikasi,
            proof: 'payments/bukti.jpg',
        );

        $this->actingAs($user)
            ->patch(route('admin.bookings.status', $booking->booking_code), [
                'status' => BookingStatus::Dikonfirmasi->value,
            ]);

        $booking->refresh();

        $this->assertSame(PaymentStatus::MenungguVerifikasi, $booking->payment?->status);
        $this->assertNull($booking->payment?->rejection_reason);
    }

    public function test_the_list_shows_the_booking_snapshot_not_the_current_product(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->forBusiness($user->business)->create(['name' => 'Nama Lama']);
        $booking = $this->booking(
            $user->business,
            product: $product,
            productName: 'Nama Lama',
        );

        $product->update(['name' => 'Nama Baru']);

        $this->actingAs($user)
            ->get(route('admin.bookings.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('bookings.data', 1)
                ->where('bookings.data.0.product_label', 'Nama Lama')
            );
    }

    public function test_the_detail_survives_a_deleted_product(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->forBusiness($user->business)->create();
        $product = Product::factory()->forBusiness($user->business)->withCategory($category)->create();
        $booking = $this->booking($user->business, product: $product, productName: 'Tenda Dome');

        $product->delete();

        $this->actingAs($user)
            ->get(route('admin.bookings.show', $booking->booking_code))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('booking.items', 1)
                ->where('booking.items.0.product_name', 'Tenda Dome')
            );
    }

    public function test_the_list_sends_every_booking_status_as_a_filter_option(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.bookings.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('statusOptions', count(BookingStatus::cases()))
                ->where('statusOptions.0.value', BookingStatus::MenungguKonfirmasi->value)
                ->where('statusOptions.1.value', BookingStatus::Dikonfirmasi->value)
                ->where('statusOptions.2.value', BookingStatus::SedangDisewa->value)
                ->where('statusOptions.3.value', BookingStatus::Selesai->value)
                ->where('statusOptions.4.value', BookingStatus::Dibatalkan->value)
            );
    }

    /**
     * Booking lengkap dengan satu item, satu pembayaran, dan riwayat awal.
     */
    private function booking(
        Business $business,
        ?Customer $customer = null,
        ?Product $product = null,
        BookingStatus $status = BookingStatus::MenungguKonfirmasi,
        PaymentStatus $paymentStatus = PaymentStatus::BelumDibayar,
        string $productName = 'Tenda Dome',
        ?string $proof = null,
        ?string $startDate = null,
        ?string $endDate = null,
    ): Booking {
        $customer ??= Customer::factory()->create();
        $product ??= Product::factory()->forBusiness($business)->create();
        $startDate ??= '2026-10-10';
        $endDate ??= '2026-10-12';
        $method = $paymentStatus === PaymentStatus::BelumDibayar
            ? PaymentMethodType::Cash
            : PaymentMethodType::Qris;

        $booking = Booking::factory()->forBusiness($business)->create([
            'customer_id' => $customer->getKey(),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_days' => 2,
            'subtotal' => 150000,
            'total' => 150000,
            'payment_method' => $method,
            'payment_status' => $paymentStatus,
            'booking_status' => $status,
        ]);

        BookingItem::factory()->create([
            'booking_id' => $booking->getKey(),
            'product_id' => $product->getKey(),
            'product_name' => $productName,
            'price' => 75000,
            'quantity' => 1,
            'total_days' => 2,
            'subtotal' => 150000,
        ]);

        Payment::factory()->create([
            'booking_id' => $booking->getKey(),
            'business_id' => $business->getKey(),
            'method' => $method,
            'amount' => 150000,
            'proof' => $proof,
            'status' => $paymentStatus,
        ]);

        BookingStatusHistory::factory()->created()->create([
            'booking_id' => $booking->getKey(),
        ]);

        return $booking;
    }

    /**
     * Sisa stok satu produk pada periode booking.
     *
     * Menghitung ulang lewat `AvailabilityService` yang sama dengan halaman
     * katalog, supaya test ini memeriksa dampaknya pada ketersediaan yang dibaca
     * penyewa, bukan hanya pada angka status di database.
     */
    private function availableStock(Product $product, Booking $booking): int
    {
        return app(AvailabilityService::class)->availableUnits(
            $product,
            $booking->start_date->toDateString(),
            $booking->end_date->toDateString(),
        );
    }
}
