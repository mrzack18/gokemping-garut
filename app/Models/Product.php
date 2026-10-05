<?php

namespace App\Models;

use App\Models\Concerns\HasBusiness;
use App\Support\ReservedProductSlugs;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $business_id
 * @property int|null $category_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property array<string, mixed>|null $specification
 * @property string|null $rental_terms
 * @property int $price
 * @property string $price_unit
 * @property int $stock
 * @property bool $is_active
 * @property Carbon|null $deleted_at
 */
#[Fillable([
    'business_id',
    'category_id',
    'name',
    'slug',
    'description',
    'specification',
    'rental_terms',
    'price',
    'price_unit',
    'stock',
    'is_active',
])]
class Product extends Model
{
    /**
     * @use HasBusiness<Product>
     * @use HasFactory<ProductFactory>
     */
    use HasBusiness, HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'specification' => 'array',
            'price' => 'integer',
            'stock' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return HasMany<ProductImage, $this> */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    /** @return HasMany<BookingItem, $this> */
    public function bookingItems(): HasMany
    {
        return $this->hasMany(BookingItem::class);
    }

    /**
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Slug unik untuk produk baru di unit bisnis `$business`.
     *
     * Keunikan dihitung di dalam satu unit bisnis, bukan global, karena dua unit
     * boleh memakai nama produk yang sama.
     *
     * Tabrakan nama diberi akhiran angka, bukan ditolak ke admin, supaya nama
     * umum seperti "Tenda Dome 4 Person" tetap bisa dipakai lebih dari sekali.
     *
     * Slug yang terlarang untuk path publik (`ReservedProductSlugs`) ikut diberi
     * akhiran, bukan menolak nama produknya. Nama "Success" atau "Payment" tetap
     * boleh dipakai, hanya URL-nya menjadi `success-2` atau `payment-2`. Menolak
     * nama produk karena nama route akan terasa seperti aplikasi yang melarang
     * istilah yang sedang dipakai pelanggan.
     *
     * Nama yang tidak menghasilkan slug, misalnya hanya tanda baca atau angka
     * saja, memakai `produk` sebagai dasarnya supaya URL tetap bisa dibaca.
     */
    public static function generateSlug(Business $business, string $name): string
    {
        $base = Str::slug($name);

        // Basis akhir dipakai juga untuk semua nomor urut. Kalau angka urut
        // ditambahkan ke `$base` yang kosong, hasilnya cuma `-2`: URL yang tidak
        // bisa dibaca dan tidak pernah bisa diklik dengan benar.
        $prefix = $base === '' ? 'produk' : $base;

        $slug = $prefix;
        $suffix = 2;

        $query = static::query()->forBusiness($business);

        while (ReservedProductSlugs::isReserved($slug) || $query->where($query->qualifyColumn('slug'), $slug)->exists()) {
            $slug = $prefix.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    /**
     * Apakah produk ini pernah dipakai pada booking.
     *
     * Dipakai admin untuk memberi tahu jumlah booking yang ikut terkait saat
     * produk dihapus (soft delete).
     */
    public function hasBookingItems(): bool
    {
        return $this->bookingItems()->exists();
    }
}
