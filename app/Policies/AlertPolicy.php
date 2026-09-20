<?php

namespace App\Policies;

use App\Models\User;

class AlertPolicy
{
    /**
     * Admin can do any action, the rest can't
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->is_admin) {
            return true;
        }

        return false;
    }

    public function viewAny(User $user): bool
    {
        return $user->is_admin;
    }

    public function create(User $user): bool
    {
        return $user->is_admin;
    }
}
