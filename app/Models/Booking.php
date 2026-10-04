<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethodType;
use App\Enums\PaymentStatus;
use App\Models\Concerns\HasBusiness;
use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $booking_code
 * @property int $business_id
 * @property int $customer_id
 * @property Carbon $start_date
 * @property Carbon $end_date
 * @property int $total_days
 * @property int $subtotal
 * @property int $total
 * @property PaymentMethodType $payment_method
 * @property PaymentStatus $payment_status
 * @property BookingStatus $booking_status
 * @property int|null $renter_count
 * @property string|null $notes
 */
#[Fillable([
    'booking_code',
    'business_id',
    'customer_id',
    'start_date',
    'end_date',
    'total_days',
    'subtotal',
    'total',
    'payment_method',
    'payment_status',
    'booking_status',
    'renter_count',
    'notes',
    'confirmed_at',
    'started_at',
    'completed_at',
    'cancelled_at',
    'cancellation_reason',
])]
class Booking extends Model
{
    /**
     * @use HasBusiness<Booking>
     * @use HasFactory<BookingFactory>
     */
    use HasBusiness, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'total_days' => 'integer',
            'subtotal' => 'integer',
            'total' => 'integer',
            'renter_count' => 'integer',
            'payment_method' => PaymentMethodType::class,
            'payment_status' => PaymentStatus::class,
            'booking_status' => BookingStatus::class,
            'confirmed_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** @return HasMany<BookingItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(BookingItem::class);
    }

    /** @return HasOne<Payment, $this> */
    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    /**
     * Booking yang periodenya beririsan dengan rentang yang diminta, dipakai
     * untuk menghitung stok terpakai pada pengecekan ketersediaan (BR-04).
     *
     * Rentang memakai tanggal selesai eksklusif karena PRD section 11
     * menghitung sewa 10 Oktober sampai 12 Oktober sebagai 2 hari. Tanggal
     * selesai adalah batas pengembalian, bukan hari sewa, sehingga barang
     * sudah bisa disewa lagi pada tanggal tersebut. Booking 10 sampai 12
     * beririsan dengan permintaan mulai 12, tapi tidak beririsan dengan
     * permintaan mulai 13.
     *
     * @param  Builder<Booking>  $query
     * @return Builder<Booking>
     */
    public function scopeOverlappingPeriod(Builder $query, Carbon|string $start, Carbon|string $end): Builder
    {
        return $query->whereDate('start_date', '<', $end)
            ->whereDate('end_date', '>', $start);
    }

    /**
     * @param  Builder<Booking>  $query
     * @return Builder<Booking>
     */
    public function scopeHoldingStock(Builder $query): Builder
    {
        return $query->whereIn(
            'booking_status',
            array_values(array_filter(
                BookingStatus::cases(),
                fn (BookingStatus $status): bool => $status->holdsStock(),
            )),
        );
    }

    /**
     * @param  Builder<Booking>  $query
     * @return Builder<Booking>
     */
    public function scopeStatus(Builder $query, BookingStatus $status): Builder
    {
        return $query->where('booking_status', $status->value);
    }

    /**
     * Jumlah unit yang disewa pada satu booking.
     *
     * @return Attribute<int, never>
     */
    protected function totalQuantity(): Attribute
    {
        return Attribute::get(fn (): int => (int) $this->items->sum('quantity'));
    }
}
