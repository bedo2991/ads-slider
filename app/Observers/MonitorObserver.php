<?php

namespace App\Observers;

use App\Models\Monitor;
use App\Providers\ItemCreated;
use App\Providers\ItemDeleted;
use App\Providers\ItemUpdated;

class MonitorObserver
{
    /**
     * Handle the Monitor "created" event.
     */
    public function created(Monitor $monitor): void
    {
        event(new ItemCreated('m', $monitor));
    }

    /**
     * Handle the Monitor "updated" event.
     */
    public function updated(Monitor $monitor): void
    {
        event(new ItemUpdated('m', $monitor));
    }

    /**
     * Handle the Monitor "deleted" event.
     */
    public function deleted(Monitor $monitor): void
    {
        event(new ItemDeleted('m', (object) ['id' => $monitor->id, 'realm_id' => $monitor->realm_id]));
    }

    /**
     * Handle the Monitor "restored" event.
     */
    public function restored(Monitor $monitor): void
    {
        event(new ItemCreated('m', $monitor));
    }

    /**
     * Handle the Monitor "force deleted" event.
     */
    public function forceDeleted(Monitor $monitor): void
    {
        event(new ItemDeleted('m', (object) ['id' => $monitor->id, 'realm_id' => $monitor->realm_id]));
    }
}
