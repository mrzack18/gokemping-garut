<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Database\Eloquent\Model;

/**
 * Base policy untuk seluruh model yang memakai trait HasBusiness.
 *
 * Admin hanya boleh melihat data unit bisnisnya sendiri (BR-05). Model yang
 * tidak punya business_id, seperti Customer, memakai CustomerPolicy terpisah.
 */
abstract class BusinessPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Model $model): bool
    {
        return $this->sameBusiness($user, $model);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Model $model): bool
    {
        return $this->sameBusiness($user, $model);
    }

    public function delete(User $user, Model $model): bool
    {
        return $this->sameBusiness($user, $model);
    }

    protected function sameBusiness(User $user, Model $model): bool
    {
        $businessId = $model->getAttribute('business_id');

        return $businessId !== null && (int) $businessId === (int) $user->business_id;
    }
}
