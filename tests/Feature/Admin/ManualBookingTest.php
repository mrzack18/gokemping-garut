<?php

namespace Tests\Feature\Admin;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Product;
use App\Models\User;
use App\Services\AvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManualBookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_only_active_in_stock_products_for_manual_booking(): void
    {
        $admin = User::factory()->create();
        $product = Product::factory()->forBusiness($admin->business)->create([
            'name' => 'Tenda Dome',
            'stock' => 3,
        ]);
        Product::factory()->forBusiness($admin->business)->inactive()->create();
        Product::factory()->forBusiness($admin->business)->create(['stock' => 0]);

        $otherAdmin = User::factory()->create();
        Product::factory()->forBusiness($otherAdmin->business)->create();

        $this->actingAs($admin)
            ->get(route('admin.bookings.manual.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/bookings/manual')
                ->has('products', 1)
                ->where('products.0.id', $product->getKey())
                ->where('products.0.name', 'Tenda Dome')
                ->has('paymentMethods', 3)
            );
    }

    public function test_admin_can_record_a_walk_in_booking_and_mark_it_in_use_and_paid(): void
    {
        $admin = User::factory()->create();
        $business = $admin->business;
        $product = Product::factory()->forBusiness($business)->create([
            'name' => 'Tenda Dome',
            'price' => 25000,
            'price_unit' => 'hari',
            'stock' => 3,
        ]);

        $response = $this->actingAs($admin)
            ->post(
                route('admin.bookings.manual.store'),
                $this->validPayload($product),
            );

        $booking = Booking::query()->firstOrFail();

        $response->assertRedirect(route('admin.bookings.show', $booking));

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->getKey(),
            'business_id' => $business->getKey(),
            'booking_status' => BookingStatus::SedangDisewa->value,
            'payment_status' => PaymentStatus::Lunas->value,
            'total_days' => 1,
            'subtotal' => 50000,
            'total' => 50000,
            'renter_count' => null,
        ]);

        $this->assertDatabaseHas('customers', [
            'id' => $booking->customer_id,
            'name' => 'Penyewa Langsung',
            'whatsapp' => '6281234567890',
            'nik' => '1234567890123456',
        ]);

        $this->assertDatabaseHas('booking_items', [
            'booking_id' => $booking->getKey(),
            'product_id' => $product->getKey(),
            'product_name' => 'Tenda Dome',
            'price' => 25000,
            'quantity' => 2,
            'total_days' => 1,
            'subtotal' => 50000,
        ]);

        $this->assertDatabaseHas('payments', [
            'booking_id' => $booking->getKey(),
            'business_id' => $business->getKey(),
            'method' => 'cash',
            'amount' => 50000,
            'status' => PaymentStatus::Lunas->value,
            'verified_by' => $admin->getKey(),
        ]);

        $this->assertDatabaseHas('booking_status_histories', [
            'booking_id' => $booking->getKey(),
            'from_status' => null,
            'to_status' => BookingStatus::Dikonfirmasi->value,
            'changed_by' => $admin->getKey(),
        ]);
        $this->assertDatabaseHas('booking_status_histories', [
            'booking_id' => $booking->getKey(),
            'from_status' => BookingStatus::Dikonfirmasi->value,
            'to_status' => BookingStatus::SedangDisewa->value,
            'changed_by' => $admin->getKey(),
        ]);

        self::assertSame(
            1,
            app(AvailabilityService::class)->availableUnits(
                $product->fresh(),
                today(),
                today()->addDay(),
            ),
        );
    }

    public function test_manual_booking_rechecks_stock_and_does_not_overbook(): void
    {
        $admin = User::factory()->create();
        $product = Product::factory()->forBusiness($admin->business)->create([
            'stock' => 1,
        ]);

        $response = $this->actingAs($admin)
            ->post(
                route('admin.bookings.manual.store'),
                $this->validPayload($product, ['quantity' => 1]),
            );

        $response->assertSessionHasNoErrors();

        $booking = Booking::query()->firstOrFail();
        $response->assertRedirect(route('admin.bookings.show', $booking));

        $this->from(route('admin.bookings.manual.create'))
            ->post(
                route('admin.bookings.manual.store'),
                $this->validPayload($product, ['quantity' => 1]),
            )
            ->assertRedirect(route('admin.bookings.manual.create'))
            ->assertSessionHasErrors('quantity');

        self::assertSame(1, Booking::query()->count());
    }

    public function test_manual_availability_endpoint_reports_stock_reserved_by_existing_booking(): void
    {
        $admin = User::factory()->create();
        $product = Product::factory()->forBusiness($admin->business)->create([
            'stock' => 2,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.bookings.manual.store'), $this->validPayload($product, [
                'quantity' => 1,
            ]))
            ->assertSessionHasNoErrors();

        $this->actingAs($admin)
            ->getJson(route('admin.bookings.manual.availability', [
                'product_id' => $product->getKey(),
                'start_date' => today()->toDateString(),
                'end_date' => today()->addDay()->toDateString(),
                'quantity' => 2,
            ]))
            ->assertOk()
            ->assertJsonPath('stock', 2)
            ->assertJsonPath('available', 1)
            ->assertJsonPath('requested', 2)
            ->assertJsonPath('is_available', false);
    }

    public function test_admin_can_record_a_future_manual_reservation_without_marking_it_paid(): void
    {
        $admin = User::factory()->create();
        $product = Product::factory()->forBusiness($admin->business)->create([
            'price' => 40000,
            'stock' => 2,
        ]);
        $start = today()->addDays(3);
        $end = today()->addDays(4);

        $this->actingAs($admin)
            ->post(route('admin.bookings.manual.store'), $this->validPayload($product, [
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'quantity' => 1,
                'is_paid' => false,
                'start_rental_now' => false,
            ]))
            ->assertSessionHasNoErrors();

        $booking = Booking::query()->firstOrFail();

        self::assertSame(BookingStatus::Dikonfirmasi, $booking->booking_status);
        self::assertSame(PaymentStatus::BelumDibayar, $booking->payment_status);
        self::assertNull($booking->started_at);
        self::assertNotNull($booking->confirmed_at);

        $this->assertDatabaseHas('payments', [
            'booking_id' => $booking->getKey(),
            'status' => PaymentStatus::BelumDibayar->value,
            'verified_by' => null,
            'verified_at' => null,
        ]);
        $this->assertDatabaseHas('booking_status_histories', [
            'booking_id' => $booking->getKey(),
            'from_status' => null,
            'to_status' => BookingStatus::Dikonfirmasi->value,
        ]);
    }

    public function test_admin_cannot_select_a_product_from_another_business(): void
    {
        $admin = User::factory()->create();
        $otherAdmin = User::factory()->create();
        $foreignProduct = Product::factory()
            ->forBusiness($otherAdmin->business)
            ->create();

        $payload = $this->validPayload($foreignProduct);

        $this->actingAs($admin)
            ->from(route('admin.bookings.manual.create'))
            ->post(route('admin.bookings.manual.store'), $payload)
            ->assertRedirect(route('admin.bookings.manual.create'))
            ->assertSessionHasErrors('product_id');

        self::assertSame(0, Booking::query()->count());
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(Product $product, array $overrides = []): array
    {
        return array_merge([
            'product_id' => $product->getKey(),
            'start_date' => today()->toDateString(),
            'end_date' => today()->addDay()->toDateString(),
            'quantity' => 2,
            'name' => 'Penyewa Langsung',
            'whatsapp' => '081234567890',
            'email' => '',
            'nik' => '1234567890123456',
            'address' => 'Jalan Merdeka 1',
            'city' => 'Garut',
            'notes' => 'Sewa langsung di lokasi',
            'renter_count' => '1',
            'payment_method' => 'cash',
            'is_paid' => true,
            'start_rental_now' => true,
        ], $overrides);
    }
}
