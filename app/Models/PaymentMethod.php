<?php

namespace App\Models;

use App\Enums\PaymentMethodType;
use App\Models\Concerns\HasBusiness;
use Database\Factories\PaymentMethodFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Konfigurasi metode pembayaran per unit bisnis. Satu baris per metode.
 *
 * @property int $id
 * @property int $business_id
 * @property PaymentMethodType $type
 * @property string|null $merchant_name
 * @property string|null $qris_image
 * @property string|null $bank_name
 * @property string|null $account_number
 * @property string|null $account_name
 * @property string|null $instructions
 * @property bool $is_active
 */
#[Fillable([
    'business_id',
    'type',
    'merchant_name',
    'qris_image',
    'bank_name',
    'account_number',
    'account_name',
    'instructions',
    'is_active',
])]
class PaymentMethod extends Model
{
    /**
     * @use HasBusiness<PaymentMethod>
     * @use HasFactory<PaymentMethodFactory>
     */
    use HasBusiness, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => PaymentMethodType::class,
            'is_active' => 'boolean',
        ];
    }

    protected $appends = ['qris_image_url'];

    /**
     * @param  Builder<PaymentMethod>  $query
     * @return Builder<PaymentMethod>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<PaymentMethod>  $query
     * @return Builder<PaymentMethod>
     */
    public function scopeOfType(Builder $query, PaymentMethodType $type): Builder
    {
        return $query->where('type', $type->value);
    }

    public function getQrisImageUrlAttribute(): ?string
    {
        return $this->qris_image === null ? null : Storage::disk('public')->url($this->qris_image);
    }
}
