<?php

namespace App\Policies;

use App\Models\Realm;
use App\Models\User;

class RealmPolicy
{
    /**
     * Admin can do any action
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->is_admin && ! in_array($ability, ['delete', 'forceDelete'])) {
            return true;
        }

        return null;
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Realm $realm): bool
    {
        return $user->realm_id === $realm->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return (bool) $user->is_admin;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Realm $realm): bool
    {
        return ($user->is_realm_admin || $user->is_admin) && $user->realm_id === $realm->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Realm $realm): bool
    {
        // no one can delete a realm
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Realm $realm): bool
    {
        return $user->is_admin;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Realm $realm): bool
    {
        return false;
    }
}
