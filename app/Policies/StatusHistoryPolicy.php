<?php

namespace App\Policies;

use App\Models\StatusHistory;
use App\Models\User;

/**
 * Intentionally permissive: any authenticated user may manage status history entries.
 *
 * @todo Replace with role based rules once the team defines how a user maps
 *       to an owner. Routes are already restricted to verified users.
 */
class StatusHistoryPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, StatusHistory $statusHistory): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, StatusHistory $statusHistory): bool
    {
        return true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, StatusHistory $statusHistory): bool
    {
        return true;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, StatusHistory $statusHistory): bool
    {
        return true;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, StatusHistory $statusHistory): bool
    {
        return true;
    }
}
