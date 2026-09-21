<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy extends RestaurantOwnedPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isPlatformSuperAdmin() || $user->isRestaurantOwner();
    }

    public function view(User $user, Order $order): bool
    {
        return $this->canView($user, $order->restaurant_id);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Order $order): bool
    {
        return false;
    }

    public function delete(User $user, Order $order): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
