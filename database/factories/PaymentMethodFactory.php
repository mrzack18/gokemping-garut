<?php

namespace Database\Factories;

use App\Enums\PaymentMethodType;
use App\Models\Business;
use App\Models\PaymentMethod;
use Database\Factories\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentMethod>
 */
class PaymentMethodFactory extends Factory
{
    use BelongsToBusiness;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'type' => PaymentMethodType::Cash,
            'merchant_name' => null,
            'qris_image' => null,
            'bank_name' => null,
            'account_number' => null,
            'account_name' => null,
            'instructions' => null,
            'is_active' => true,
        ];
    }

    public function cash(string $instructions = 'Pembayaran dilakukan langsung di lokasi.'): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => PaymentMethodType::Cash,
            'instructions' => $instructions,
        ]);
    }

    public function qris(string $merchantName = 'GoKemping'): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => PaymentMethodType::Qris,
            'merchant_name' => $merchantName,
            'qris_image' => 'qris/'.fake()->uuid().'.png',
        ]);
    }

    public function bankTransfer(
        string $bankName = 'BCA',
        string $accountNumber = '1234567890',
        string $accountName = 'GoKemping',
    ): static {
        return $this->state(fn (array $attributes) => [
            'type' => PaymentMethodType::BankTransfer,
            'bank_name' => $bankName,
            'account_number' => $accountNumber,
            'account_name' => $accountName,
        ]);
    }
}
