<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Membatasi query ke unit bisnis milik admin yang sedang login (BR-05).
 *
 * Scope ini sengaja tidak aktif bila tidak ada admin yang login, karena
 * halaman publik tidak punya notion "admin". Halaman publik wajib melakukan
 * scoping secara eksplisit memakai scopeForBusiness() pada model yang
 * memakai trait HasBusiness.
 *
 * @template TModel of Model
 *
 * @implements Scope<TModel>
 */
class BusinessScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $businessId = static::currentBusinessId();

        if ($businessId === null) {
            return;
        }

        $builder->where($model->qualifyColumn('business_id'), $businessId);
    }

    public static function currentBusinessId(): ?int
    {
        $businessId = Auth::user()?->business_id;

        return $businessId === null ? null : (int) $businessId;
    }

    /**
     * Menonaktifkan scope untuk satu query, dipakai pada operasi lintas unit
     * bisnis yang memang sah (mis. halaman detail publik).
     *
     * @template TQueryModel of Model
     *
     * @param  Builder<TQueryModel>  $query
     * @return Builder<TQueryModel>
     */
    public static function withoutBusinessScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope(static::class);
    }
}
