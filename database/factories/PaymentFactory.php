<?php

namespace Database\Factories;

use App\Enums\PaymentMethodType;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Business;
use App\Models\Payment;
use Database\Factories\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    use BelongsToBusiness;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'business_id' => Business::factory(),
            'method' => fake()->randomElement(PaymentMethodType::cases()),
            'amount' => fake()->randomElement([75000, 150000, 300000, 450000]),
            'proof' => null,
            'status' => PaymentStatus::BelumDibayar,
            'rejection_reason' => null,
            'verified_at' => null,
            'verified_by' => null,
        ];
    }

    public function cash(): static
    {
        return $this->state(fn (array $attributes) => [
            'method' => PaymentMethodType::Cash,
            'proof' => null,
            'status' => PaymentStatus::BelumDibayar,
        ]);
    }

    public function qris(): static
    {
        return $this->state(fn (array $attributes) => [
            'method' => PaymentMethodType::Qris,
            'proof' => 'payments/'.fake()->uuid().'.jpg',
            'status' => PaymentStatus::MenungguVerifikasi,
        ]);
    }

    public function bankTransfer(): static
    {
        return $this->state(fn (array $attributes) => [
            'method' => PaymentMethodType::BankTransfer,
            'proof' => 'payments/'.fake()->uuid().'.jpg',
            'status' => PaymentStatus::MenungguVerifikasi,
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Lunas,
            'verified_at' => now(),
        ]);
    }

    public function rejected(string $reason = 'Bukti transfer tidak terbaca'): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Ditolak,
            'rejection_reason' => $reason,
        ]);
    }
}
