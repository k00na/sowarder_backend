<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy extends RestaurantOwnedPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isPlatformSuperAdmin() || $user->isRestaurantOwner();
    }

    public function view(User $user, Product $product): bool
    {
        return $this->canView($user, $product->restaurant_id);
    }

    public function create(User $user): bool
    {
        return $user->isRestaurantOwner();
    }

    public function update(User $user, Product $product): bool
    {
        return $this->canManage($user, $product->restaurant_id);
    }

    public function delete(User $user, Product $product): bool
    {
        return $this->canManage($user, $product->restaurant_id);
    }
}
