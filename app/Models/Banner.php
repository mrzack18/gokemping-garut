<?php

namespace App\Models;

use App\Models\Concerns\HasBusiness;
use Database\Factories\BannerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Banner hero carousel milik satu unit bisnis (PRD section 28, ROADMAP 5.4).
 *
 * Gambar banner disimpan di disk `public` karena dibaca pengunjung lewat URL,
 * dan setiap unit punya daftar bannernya sendiri. `sort_order` menentukan
 * urutan tayang, dan banner nonaktif tidak ikut dikirim ke landing page.
 *
 * @property int $id
 * @property int $business_id
 * @property string $title
 * @property string|null $subtitle
 * @property string $image
 * @property string|null $link_url
 * @property int $sort_order
 * @property bool $is_active
 */
#[Fillable([
    'business_id',
    'title',
    'subtitle',
    'image',
    'link_url',
    'sort_order',
    'is_active',
])]
class Banner extends Model
{
    /**
     * @use HasBusiness<Banner>
     * @use HasFactory<BannerFactory>
     */
    use HasBusiness, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected $appends = ['image_url'];

    /**
     * @param  Builder<Banner>  $query
     * @return Builder<Banner>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<Banner>  $query
     * @return Builder<Banner>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function getImageUrlAttribute(): ?string
    {
        return Storage::disk('public')->url($this->image);
    }
}
