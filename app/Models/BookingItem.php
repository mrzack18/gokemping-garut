<?php

namespace App\Models;

use Database\Factories\BookingItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Nilai transaksi disalin ke sini saat booking dibuat agar perubahan harga
 * produk di kemudian hari tidak mengubah transaksi lama (BR-09).
 *
 * @property int $id
 * @property int $booking_id
 * @property int|null $product_id
 * @property string $product_name
 * @property string $price_unit
 * @property int $price
 * @property int $quantity
 * @property int $total_days
 * @property int $subtotal
 */
#[Fillable([
    'booking_id',
    'product_id',
    'product_name',
    'price_unit',
    'price',
    'quantity',
    'total_days',
    'subtotal',
])]
class BookingItem extends Model
{
    /** @use HasFactory<BookingItemFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'quantity' => 'integer',
            'total_days' => 'integer',
            'subtotal' => 'integer',
        ];
    }

    /** @return BelongsTo<Booking, $this> */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
