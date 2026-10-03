<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookingItem>
 */
class BookingItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $price = fake()->randomElement([50000, 75000, 100000]);
        $quantity = fake()->numberBetween(1, 3);
        $totalDays = fake()->numberBetween(1, 5);

        return [
            'booking_id' => Booking::factory(),
            'product_id' => Product::factory(),
            'product_name' => fake()->words(2, true),
            'price_unit' => 'hari',
            'price' => $price,
            'quantity' => $quantity,
            'total_days' => $totalDays,
            'subtotal' => $price * $quantity * $totalDays,
        ];
    }
}
