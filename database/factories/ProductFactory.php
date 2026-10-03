<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Category;
use App\Models\Product;
use Database\Factories\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    use BelongsToBusiness;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = Str::title(fake()->unique()->sentence(3));

        return [
            'business_id' => Business::factory(),
            // Kategori dipasang eksplisit lewat withCategory() supaya produk
            // dan kategori tidak pernah berada pada unit bisnis berbeda.
            'category_id' => null,
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->paragraph(),
            'specification' => [
                'Kapasitas' => fake()->numberBetween(1, 8).' orang',
                'Berat' => fake()->numberBetween(500, 5000).' gram',
                'Warna' => fake()->randomElement(['Hijau', 'Biru', 'Merah', 'Hitam']),
            ],
            'rental_terms' => 'Barang harus dikembalikan dalam kondisi bersih dan utuh.',
            'price' => fake()->randomElement([25000, 50000, 75000, 100000, 150000]),
            'price_unit' => 'hari',
            'stock' => fake()->numberBetween(1, 10),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Memasang kategori yang sudah ada ke produk.
     */
    public function withCategory(Category $category): static
    {
        return $this->state(fn (array $attributes) => [
            'category_id' => $category->getKey(),
        ]);
    }
}
