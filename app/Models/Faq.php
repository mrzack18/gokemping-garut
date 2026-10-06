<?php

namespace App\Models;

use App\Models\Concerns\HasBusiness;
use Database\Factories\FaqFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Pertanyaan yang sering diajukan milik satu unit bisnis (PRD section 28,
 * ROADMAP 5.4).
 *
 * FAQ dikelola per unit karena jawabannya bisa berbeda: kebijakan deposit,
 * jam operasional, dan ketentuan sewa tiap unit tidak harus sama. FAQ nonaktif
 * tidak ikut dikirim ke landing page.
 *
 * @property int $id
 * @property int $business_id
 * @property string $question
 * @property string $answer
 * @property int $sort_order
 * @property bool $is_active
 */
#[Fillable([
    'business_id',
    'question',
    'answer',
    'sort_order',
    'is_active',
])]
class Faq extends Model
{
    /**
     * @use HasBusiness<Faq>
     * @use HasFactory<FaqFactory>
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

    /**
     * @param  Builder<Faq>  $query
     * @return Builder<Faq>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<Faq>  $query
     * @return Builder<Faq>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
