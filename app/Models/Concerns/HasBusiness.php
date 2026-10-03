<?php

namespace App\Models\Concerns;

use App\Models\Business;
use App\Models\Scopes\BusinessScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @template TModel of Model
 */
trait HasBusiness
{
    public static function bootHasBusiness(): void
    {
        static::addGlobalScope(new BusinessScope);
    }

    /**
     * Scoping eksplisit ke satu unit bisnis. Wajib dipakai pada halaman
     * publik karena BusinessScope tidak aktif tanpa admin yang login.
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public function scopeForBusiness(Builder $query, Business|int $business): Builder
    {
        return $query->where(
            $this->qualifyColumn('business_id'),
            $business instanceof Business ? $business->getKey() : $business,
        );
    }

    /**
     * @return BelongsTo<Business, $this>
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function getBusinessId(): ?int
    {
        $businessId = $this->getAttribute('business_id');

        return $businessId === null ? null : (int) $businessId;
    }
}
