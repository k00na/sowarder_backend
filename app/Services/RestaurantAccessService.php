<?php

namespace App\Services;

use App\Enums\RestaurantStatus;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RestaurantAccessService
{
    /**
     * Disable a restaurant and revoke all cashier Sanctum tokens (hard cut).
     */
    public function disable(Restaurant $restaurant): Restaurant
    {
        return DB::transaction(function () use ($restaurant): Restaurant {
            $restaurant->update(['status' => RestaurantStatus::Disabled]);

            $this->revokeCashierTokens($restaurant);

            return $restaurant->refresh();
        });
    }

    /**
     * Enforce hard-cut side effects when status is already (or newly) disabled.
     */
    public function enforceHardCutIfDisabled(Restaurant $restaurant): void
    {
        if ($restaurant->status !== RestaurantStatus::Disabled) {
            return;
        }

        $this->revokeCashierTokens($restaurant);
    }

    public function revokeCashierTokens(Restaurant $restaurant): void
    {
        User::query()
            ->where('restaurant_id', $restaurant->id)
            ->each(function (User $user): void {
                $user->tokens()->where('name', User::CASHIER_TOKEN_NAME)->delete();
            });
    }
}
