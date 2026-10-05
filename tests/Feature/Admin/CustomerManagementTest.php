<?php

namespace Tests\Feature\Admin;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Business;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Manajemen penyewa dari sisi admin (PRD section 25, ROADMAP 4.5).
 *
 * Penyewa sengaja tidak punya `business_id`, jadi hampir semua test di sini
 * memeriksa satu hal yang sama: angka dan riwayat yang dilihat admin harus
 * berasal dari booking di unit bisnisnya saja, walaupun baris customer-nya
 * dipakai bersama unit lain.
 */
class CustomerManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get(route('admin.customers.index'))
            ->assertRedirect(route('login'));
    }

    public function test_the_list_shows_customers_with_bookings_in_the_business(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create(['name' => 'Zaki Pratama']);
        $this->booking($user->business, $customer);

        $this->actingAs($user)
            ->get(route('admin.customers.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/customers/index')
                ->has('customers.data', 1)
                ->where('customers.data.0.name', 'Zaki Pratama')
                ->where('customers.data.0.bookings_count', 1)
            );
    }

    public function test_the_list_does_not_show_customers_without_bookings_in_the_business(): void
    {
        $user = User::factory()->create();
        Customer::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.customers.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('customers.data', 0)
            );
    }

    public function test_the_list_only_shows_customers_from_the_same_business(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $mine = Customer::factory()->create(['name' => 'Penyewa Sendiri']);
        $foreign = Customer::factory()->create(['name' => 'Penyewa Unit Lain']);

        $this->booking($user->business, $mine);
        $this->booking($other->business, $foreign);

        $this->actingAs($user)
            ->get(route('admin.customers.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('customers.data', 1)
                ->where('customers.data.0.name', 'Penyewa Sendiri')
            );
    }

    public function test_a_customer_from_another_business_is_not_found(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $foreign = Customer::factory()->create();
        $this->booking($other->business, $foreign);

        $this->actingAs($user)
            ->get(route('admin.customers.show', $foreign))
            ->assertNotFound();
    }

    public function test_a_customer_without_any_booking_is_not_found(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.customers.show', $customer))
            ->assertNotFound();
    }

    public function test_the_list_can_be_searched_by_name(): void
    {
        $user = User::factory()->create();
        $wanted = Customer::factory()->create(['name' => 'Zaki Pratama']);
        $other = Customer::factory()->create(['name' => 'Bagus Setiawan']);

        $this->booking($user->business, $wanted);
        $this->booking($user->business, $other);

        $this->actingAs($user)
            ->get(route('admin.customers.index', ['q' => 'Zaki']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('customers.data', 1)
                ->where('customers.data.0.name', 'Zaki Pratama')
                ->where('filters.q', 'Zaki')
            );
    }

    public function test_the_list_can_be_searched_by_whatsapp(): void
    {
        $user = User::factory()->create();
        $wanted = Customer::factory()->withWhatsapp('628123456789')->create();
        $other = Customer::factory()->withWhatsapp('628987654321')->create();

        $this->booking($user->business, $wanted);
        $this->booking($user->business, $other);

        $this->actingAs($user)
            ->get(route('admin.customers.index', ['q' => '8123456']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('customers.data', 1)
                ->where('customers.data.0.whatsapp', '628123456789')
            );
    }

    /**
     * NIK dicari dalam bentuk aslinya, walaupun yang tampil adalah bentuk
     * tersamar. Kata kunci yang dipakai ada di tengah NIK, jadi pencarian ini
     * tidak akan cocok kalau yang dicari adalah nilai tersamarnya.
     */
    public function test_the_list_can_be_searched_by_nik(): void
    {
        $user = User::factory()->create();
        $wanted = Customer::factory()
            ->withWhatsapp('628111111111')
            ->create(['nik' => '3273011234560001']);
        $other = Customer::factory()
            ->withWhatsapp('628222222222')
            ->create(['nik' => '3273999999990000']);

        $this->booking($user->business, $wanted);
        $this->booking($user->business, $other);

        $this->actingAs($user)
            ->get(route('admin.customers.index', ['q' => '123456']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('customers.data', 1)
                ->where('customers.data.0.nik', '**********560001')
            );
    }

    public function test_the_search_escapes_like_wildcards(): void
    {
        $user = User::factory()->create();
        $this->booking($user->business, Customer::factory()->create());

        $this->actingAs($user)
            ->get(route('admin.customers.index', ['q' => '%']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('customers.data', 0)
            );
    }

    public function test_the_list_masks_the_nik(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create(['nik' => '3273011234560001']);
        $this->booking($user->business, $customer);

        $this->actingAs($user)
            ->get(route('admin.customers.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('customers.data.0.nik', '**********560001')
                ->where('customers.data.0.nik', fn (string $nik): bool => ! str_contains($nik, '3273011234560001'))
            );
    }

    public function test_the_detail_shows_the_customer_and_the_booking_history(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create([
            'name' => 'Zaki Pratama',
            'nik' => '3273011234560001',
        ]);
        $booking = $this->booking($user->business, $customer);

        $this->actingAs($user)
            ->get(route('admin.customers.show', $customer))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/customers/show')
                ->where('customer.name', 'Zaki Pratama')
                ->where('customer.nik', '**********560001')
                ->where('customer.bookings_count', 1)
                ->has('bookings', 1)
                ->where('bookings.0.booking_code', $booking->booking_code)
            );
    }

    public function test_the_aggregates_only_count_bookings_from_the_current_business(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $customer = Customer::factory()->create();
        $this->booking($user->business, $customer, total: 100000);
        $this->booking($other->business, $customer, total: 999000);

        $this->actingAs($user)
            ->get(route('admin.customers.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('customers.data', 1)
                ->where('customers.data.0.bookings_count', 1)
                ->where('customers.data.0.total_transaction', 100000)
                ->where('customers.data.0.total_transaction_label', '100.000')
            );

        $this->actingAs($user)
            ->get(route('admin.customers.show', $customer))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('customer.bookings_count', 1)
                ->where('customer.total_transaction', 100000)
                ->has('bookings', 1)
            );
    }

    public function test_a_cancelled_booking_is_counted_but_not_summed(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();

        $this->booking($user->business, $customer, total: 150000);
        $this->booking(
            $user->business,
            $customer,
            status: BookingStatus::Dibatalkan,
            total: 50000,
        );

        $this->actingAs($user)
            ->get(route('admin.customers.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('customers.data.0.bookings_count', 2)
                ->where('customers.data.0.total_transaction', 150000)
            );
    }

    /**
     * Riwayat hanya menampilkan booking unit ini. Booking penyewa yang sama di
     * unit lain tidak ikut, walaupun baris customer-nya memang dibagi bersama.
     */
    public function test_the_history_only_lists_bookings_from_the_current_business(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $customer = Customer::factory()->create();
        $mine = $this->booking($user->business, $customer);
        $this->booking(
            $other->business,
            $customer,
            status: BookingStatus::Dibatalkan,
        );

        $this->actingAs($user)
            ->get(route('admin.customers.show', $customer))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('bookings', 1)
                ->where('bookings.0.booking_code', $mine->booking_code)
            );
    }

    /**
     * Booking penyewa di unit bisnis ini, dengan total yang bisa diatur supaya
     * angka agregat di test mudah dibaca.
     */
    private function booking(
        Business $business,
        Customer $customer,
        BookingStatus $status = BookingStatus::MenungguKonfirmasi,
        int $total = 150000,
    ): Booking {
        return Booking::factory()->forBusiness($business)->create([
            'customer_id' => $customer->getKey(),
            'subtotal' => $total,
            'total' => $total,
            'booking_status' => $status,
        ]);
    }
}
