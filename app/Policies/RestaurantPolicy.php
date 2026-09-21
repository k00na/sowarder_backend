<?php

namespace App\Policies;

use App\Models\Restaurant;
use App\Models\User;

class RestaurantPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isPlatformSuperAdmin() || $user->isRestaurantOwner();
    }

    public function view(User $user, Restaurant $restaurant): bool
    {
        if ($user->isPlatformSuperAdmin()) {
            return true;
        }

        return $user->belongsToRestaurant($restaurant->id);
    }

    public function create(User $user): bool
    {
        return $user->isPlatformSuperAdmin();
    }

    public function update(User $user, Restaurant $restaurant): bool
    {
        if ($user->isPlatformSuperAdmin()) {
            return true;
        }

        return $user->belongsToRestaurant($restaurant->id);
    }

    public function delete(User $user, Restaurant $restaurant): bool
    {
        return $user->isPlatformSuperAdmin();
    }
}
