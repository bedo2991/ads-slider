<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Access\Response;

class EventPolicy
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
        return (bool) $user->realm_id;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Event $event): bool
    {
        return $user->realm_id === $event->realm_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return (bool) $user->realm_id;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Event $event): bool
    {
        return $user->realm_id === $event->realm_id;
    }

    public function avUpdate(?User $user, Event $event): Response
    {
        // An AV is updating an event. Check if it's within the allowed time
        if (now()->lt(Carbon::parse($event->real_start_date)->subMinutes(180))) {
            return Response::deny('Sorry, the AV can only change the event from 3h before it starts');
        }
        if ($event->is_expired) {
            return Response::deny('Sorry, the AV can only change the event while it is happening');
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Event $event): bool
    {
        return ($user->is_realm_admin && $user->realm_id === $event->realm_id)
        || ($event->user_id === $user->id && $user->realm_id === $event->realm_id);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Event $event): bool
    {
        return $user->realm_id === $event->realm_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Event $event): bool
    {
        return ($user->is_realm_admin && $user->realm_id === $event->realm_id)
        || ($event->user_id === $user->id && $user->realm_id === $event->realm_id);
    }
}
