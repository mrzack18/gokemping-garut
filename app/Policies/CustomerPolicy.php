<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Customer tidak punya business_id karena satu orang bisa menyewa di kedua
 * unit bisnis. Akses admin ke data penyewa diizinkan bila penyewa tersebut
 * pernah melakukan booking pada unit bisnis admin (BR-05).
 */
class CustomerPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Customer $customer): bool
    {
        return $this->hasBookingInUserBusiness($user, $customer);
    }

    public function update(User $user, Customer $customer): bool
    {
        return $this->hasBookingInUserBusiness($user, $customer);
    }

    protected function hasBookingInUserBusiness(User $user, Customer $customer): bool
    {
        return $customer->bookings()
            ->withoutGlobalScopes()
            ->where('business_id', $user->business_id)
            ->exists();
    }
}
