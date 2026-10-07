<?php

namespace App\Policies;

use App\Models\Mitigation;
use App\Models\User;

/**
 * Intentionally permissive: any authenticated user may manage mitigations.
 *
 * @todo Replace with role based rules once the team defines how a user maps
 *       to an owner. Routes are already restricted to verified users.
 */
class MitigationPolicy
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
    public function view(User $user, Mitigation $mitigation): bool
    {
        return true;
    }
}
