<?php

namespace App\Models;

use App\Models\Concerns\HasBusiness;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $business_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property int $sort_order
 * @property bool $is_active
 */
#[Fillable([
    'business_id',
    'name',
    'slug',
    'description',
    'sort_order',
    'is_active',
])]
class Category extends Model
{
    /**
     * @use HasBusiness<Category>
     * @use HasFactory<CategoryFactory>
     */
    use HasBusiness, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @return HasMany<Product, $this> */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /** @return HasMany<Product, $this> */
    public function activeProducts(): HasMany
    {
        return $this->products()->where('is_active', true);
    }

    /**
     * Guard: kategori yang masih memiliki produk tidak boleh dihapus.
     */
    public function hasProducts(): bool
    {
        return $this->products()->exists();
    }
}
