<?php

namespace App\Broadcasting;

use App\Models\User;

class RestaurantChannel
{
    /**
     * Authorize cashier/staff access to a private restaurant channel.
     */
    public function join(User $user, int $restaurantId): bool
    {
        if ($user->isPlatformSuperAdmin()) {
            return true;
        }

        if (! $user->belongsToRestaurant($restaurantId)) {
            return false;
        }

        return $user->tokenCan(User::CASHIER_TOKEN_ABILITY) || $user->isRestaurantOwner();
    }
}
