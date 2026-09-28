<?php

namespace App\Policies;

use App\Models\Evidence;
use App\Models\User;

/**
 * Intentionally permissive: any authenticated user may manage evidence.
 *
 * @todo Replace with role based rules once the team defines how a user maps
 *       to an owner. Routes are already restricted to verified users.
 */
class EvidencePolicy
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
    public function view(User $user, Evidence $evidence): bool
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
}
