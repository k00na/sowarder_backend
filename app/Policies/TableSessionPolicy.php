<?php

namespace App\Policies;

use App\Models\TableSession;
use App\Models\User;

class TableSessionPolicy extends RestaurantOwnedPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isPlatformSuperAdmin() || $user->isRestaurantOwner();
    }

    public function view(User $user, TableSession $tableSession): bool
    {
        return $this->canView($user, $tableSession->restaurant_id);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, TableSession $tableSession): bool
    {
        return false;
    }

    public function delete(User $user, TableSession $tableSession): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    /**
     * Acknowledge (Accept Order) — cashier token holders for this restaurant only.
     * Super-admin stays read-only per the locked auth model.
     */
    public function acknowledge(User $user, TableSession $tableSession): bool
    {
        return $this->canManage($user, $tableSession->restaurant_id);
    }

    /**
     * End Session — cashier token holders for this restaurant only.
     */
    public function endSession(User $user, TableSession $tableSession): bool
    {
        return $this->canManage($user, $tableSession->restaurant_id);
    }
}
