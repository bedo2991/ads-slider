<?php

namespace App\Policies;

use App\Models\EventsImport;
use App\Models\User;

class EventsImportPolicy
{
    /**
     * Admin can do any action
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->is_admin) {
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
    public function view(User $user, EventsImport $eventsImport): bool
    {
        return $user->realm_id === $eventsImport->realm_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false; // must be an admin
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, EventsImport $eventsImport): bool
    {
        return $user->realm_id === $eventsImport->realm_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, EventsImport $eventsImport): bool
    {
        return $user->realm_id === $eventsImport->realm_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, EventsImport $eventsImport): bool
    {
        return $user->realm_id === $eventsImport->realm_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, EventsImport $eventsImport): bool
    {
        return $user->realm_id === $eventsImport->realm_id;
    }
}
