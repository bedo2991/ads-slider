<?php

namespace App\Observers;

use App\Models\HappyHour;
use App\Providers\ItemCreated;
use App\Providers\ItemDeleted;
use App\Providers\ItemUpdated;

class HappyHourObserver
{
    /**
     * Handle the HappyHour "created" event.
     */
    public function created(HappyHour $happyHour): void
    {
        event(new ItemCreated('hh', $happyHour));
    }

    /**
     * Handle the HappyHour "updated" event.
     */
    public function updated(HappyHour $happyHour): void
    {
        event(new ItemUpdated('hh', $happyHour));
    }

    /**
     * Handle the HappyHour "deleted" event.
     */
    public function deleted(HappyHour $happyHour): void
    {
        event(new ItemDeleted('hh', (object) ['id' => $happyHour->id, 'realm_id' => $happyHour->event?->realm_id]));
    }

    /**
     * Handle the HappyHour "restored" event.
     */
    public function restored(HappyHour $happyHour): void
    {
        event(new ItemCreated('hh', $happyHour));
    }

    /**
     * Handle the HappyHour "force deleted" event.
     */
    public function forceDeleted(HappyHour $happyHour): void
    {
        event(new ItemDeleted('hh', (object) ['id' => $happyHour->id, 'realm_id' => $happyHour->event?->realm_id]));
    }
}
