<?php

namespace App\Models;

use App\Enums\PaymentMethodType;
use App\Enums\PaymentStatus;
use App\Models\Concerns\HasBusiness;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $booking_id
 * @property int $business_id
 * @property PaymentMethodType $method
 * @property int $amount
 * @property string|null $proof
 * @property PaymentStatus $status
 * @property string|null $rejection_reason
 * @property Carbon|null $verified_at
 * @property int|null $verified_by
 */
#[Fillable([
    'booking_id',
    'business_id',
    'method',
    'amount',
    'proof',
    'status',
    'rejection_reason',
    'verified_at',
    'verified_by',
])]
class Payment extends Model
{
    /**
     * @use HasBusiness<Payment>
     * @use HasFactory<PaymentFactory>
     */
    use HasBusiness, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'method' => PaymentMethodType::class,
            'amount' => 'integer',
            'status' => PaymentStatus::class,
            'verified_at' => 'datetime',
        ];
    }

    protected $appends = ['proof_url'];

    /** @return BelongsTo<Booking, $this> */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /** @return BelongsTo<User, $this> */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function getProofUrlAttribute(): ?string
    {
        return $this->proof === null ? null : Storage::disk('public')->url($this->proof);
    }

    /**
     * @param  Builder<Payment>  $query
     * @return Builder<Payment>
     */
    public function scopeStatus(Builder $query, PaymentStatus $status): Builder
    {
        return $query->where('status', $status->value);
    }
}
