<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\HappyHour;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Access\Response;

class HappyHourPolicy
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
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, HappyHour $happyHour): bool
    {
        return $user->realm_id === $happyHour->event->realm_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, Event $event): bool
    {
        return $user->realm_id === $event->realm_id;
    }

    /**
     * Determine whether the Manager can create an Happy Hour
     */
    public function createAsManager(?User $user, Event $event): Response
    {
        // An AV is updating an event. Check if it's within the allowed time
        if (now()->lt(Carbon::parse($event->real_start_date)->subMinutes(180))) {
            return Response::deny('Sorry, the Manager can only change the event from 3h before it starts');
        }
        if ($event->is_expired) {
            return Response::deny('Sorry, the Manager can only change the event while it is happening');
        }
        // Allow if authenticated in same realm OR if unauthenticated (token verified by middleware)
        if (is_null($user) || $user->realm_id === $event->realm_id) {
            return Response::allow();
        }

        return Response::deny('Sorry, you can only manage happy hours in your realm');
    }

    /**
     * Determine whether the Manager can update an Happy Hour
     */
    public function updateAsManager(?User $user, HappyHour $happyHour, Event $event): Response
    {
        // An AV is updating an event. Check if it's within the allowed time
        if (now()->lt(Carbon::parse($event->real_start_date)->subMinutes(180))) {
            return Response::deny('Sorry, the AV can only change the event from 3h before it starts');
        }
        if ($event->is_expired) {
            return Response::deny('Sorry, the AV can only change the event while it is happening');
        }

        if (is_null($user) || $user->realm_id === $event->realm_id) {
            return Response::allow();
        }

        return Response::deny('Sorry, you can only update happy hours in your realm');

    }

    /**
     * Determine whether the Manager can update an Happy Hour
     */
    public function deleteAsManager(?User $user, HappyHour $happyHour, Event $event): Response
    {
        // An AV is updating an event. Check if it's within the allowed time
        if (now()->lt(Carbon::parse($event->real_start_date)->subMinutes(180))) {
            return Response::deny('Sorry, the AV can only change the event from 3h before it starts');
        }
        if ($event->is_expired) {
            return Response::deny('Sorry, the AV can only change the event while it is happening');
        }

        if (is_null($user) || $user->realm_id === $event->realm_id) {
            return Response::allow();
        }

        return Response::deny('Sorry, you can only delete happy hours in your realm');

    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, HappyHour $happyHour): bool
    {
        return $happyHour->event->realm_id === $user->realm_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, HappyHour $happyHour): bool
    {
        return $happyHour->event->realm_id === $user->realm_id;
    }
}
