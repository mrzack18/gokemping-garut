<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethodType;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Business;
use App\Models\Customer;
use Database\Factories\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    use BelongsToBusiness;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('+1 day', '+30 days');
        $totalDays = fake()->numberBetween(1, 5);
        $endDate = (clone $startDate)->modify("+{$totalDays} day");
        $price = fake()->randomElement([50000, 75000, 100000]);
        $quantity = fake()->numberBetween(1, 3);
        $subtotal = $price * $quantity * $totalDays;

        return [
            'booking_code' => strtoupper(Str::random(3)).'-'.now()->format('Ymd').'-'.fake()->unique()->numerify('###'),
            'business_id' => Business::factory(),
            'customer_id' => Customer::factory(),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_days' => $totalDays,
            'subtotal' => $subtotal,
            'total' => $subtotal,
            'payment_method' => fake()->randomElement(PaymentMethodType::cases()),
            'payment_status' => PaymentStatus::BelumDibayar,
            'booking_status' => BookingStatus::MenungguKonfirmasi,
            'renter_count' => null,
            'notes' => null,
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'booking_status' => BookingStatus::Dikonfirmasi,
            'confirmed_at' => now(),
        ]);
    }

    public function rented(): static
    {
        return $this->state(fn (array $attributes) => [
            'booking_status' => BookingStatus::SedangDisewa,
            'confirmed_at' => now(),
            'started_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'booking_status' => BookingStatus::Dibatalkan,
            'cancelled_at' => now(),
            'cancellation_reason' => 'Dibatalkan oleh admin',
        ]);
    }

    /**
     * Booking dengan periode dan status tertentu, dipakai untuk menguji
     * pengecekan ketersediaan (BR-04).
     */
    public function forPeriod(
        Business|int $business,
        string $startDate,
        string $endDate,
        BookingStatus $status = BookingStatus::Dikonfirmasi,
    ): static {
        $totalDays = max(
            1,
            (int) Carbon::parse($startDate)
                ->diffInDays(Carbon::parse($endDate)),
        );

        return $this->state(fn (array $attributes) => [
            'business_id' => $business instanceof Business ? $business->getKey() : $business,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_days' => $totalDays,
            'booking_status' => $status,
        ]);
    }
}
