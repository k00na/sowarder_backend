<?php

namespace App\Policies;

use App\Models\MenuCategory;
use App\Models\User;

class MenuCategoryPolicy extends RestaurantOwnedPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isPlatformSuperAdmin() || $user->isRestaurantOwner();
    }

    public function view(User $user, MenuCategory $menuCategory): bool
    {
        return $this->canView($user, $menuCategory->restaurant_id);
    }

    public function create(User $user): bool
    {
        return $user->isRestaurantOwner();
    }

    public function update(User $user, MenuCategory $menuCategory): bool
    {
        return $this->canManage($user, $menuCategory->restaurant_id);
    }

    public function delete(User $user, MenuCategory $menuCategory): bool
    {
        return $this->canManage($user, $menuCategory->restaurant_id);
    }
}
