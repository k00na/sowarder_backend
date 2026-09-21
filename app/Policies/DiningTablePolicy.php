<?php

namespace App\Policies;

use App\Models\DiningTable;
use App\Models\User;

class DiningTablePolicy extends RestaurantOwnedPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isPlatformSuperAdmin() || $user->isRestaurantOwner();
    }

    public function view(User $user, DiningTable $diningTable): bool
    {
        return $this->canView($user, $diningTable->restaurant_id);
    }

    public function create(User $user): bool
    {
        return $user->isRestaurantOwner();
    }

    public function update(User $user, DiningTable $diningTable): bool
    {
        return $this->canManage($user, $diningTable->restaurant_id);
    }

    public function delete(User $user, DiningTable $diningTable): bool
    {
        return $this->canManage($user, $diningTable->restaurant_id);
    }
}
