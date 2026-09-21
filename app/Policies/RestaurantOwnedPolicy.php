<?php

namespace App\Policies;

use App\Models\User;

abstract class RestaurantOwnedPolicy
{
    protected function canView(User $user, int $restaurantId): bool
    {
        if ($user->isPlatformSuperAdmin()) {
            return true;
        }

        return $user->belongsToRestaurant($restaurantId);
    }

    protected function canManage(User $user, int $restaurantId): bool
    {
        return $user->belongsToRestaurant($restaurantId);
    }
}
