<?php

namespace Database\Factories\Concerns;

use App\Models\Business;

/**
 * State factory untuk menempatkan data uji pada unit bisnis tertentu agar
 * pengujian isolasi tenant (BR-05) bisa dilakukan dengan benar.
 */
trait BelongsToBusiness
{
    public function forBusiness(Business|int $business): static
    {
        return $this->state(fn (array $attributes) => [
            'business_id' => $business instanceof Business ? $business->getKey() : $business,
        ]);
    }
}
