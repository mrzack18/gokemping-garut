<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'whatsapp' => '628'.fake()->unique()->numerify('##########'),
            'email' => fake()->unique()->safeEmail(),
            'nik' => fake()->unique()->numerify('3273##########'),
            'address' => fake()->address(),
            'city' => 'Garut',
            'notes' => null,
        ];
    }

    /**
     * Nomor WhatsApp format Indonesia tanpa tanda plus, mis. 628123456789.
     */
    public function withWhatsapp(string $whatsapp): static
    {
        return $this->state(fn (array $attributes) => [
            'whatsapp' => Str::of($whatsapp)->replaceMatches('/[^0-9]/', '')->toString(),
        ]);
    }
}
