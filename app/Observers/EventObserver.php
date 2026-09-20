<?php

namespace App\Observers;

use App\Models\Event;
use App\Providers\ItemCreated;
use App\Providers\ItemDeleted;
use App\Providers\ItemUpdated;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class EventObserver implements ShouldHandleEventsAfterCommit
{
    /**
     * Handle the Event "created" event.
     */
    public function created(Event $event): void
    {
        event(new ItemCreated('e', $event));
    }

    /**
     * Handle the Event "updated" event.
     */
    public function updated(Event $event): void
    {
        event(new ItemUpdated('e', $event));
    }

    /**
     * Handle the Event "deleted" event.
     */
    public function deleted(Event $event): void
    {
        event(new ItemDeleted('e', (object) ['id' => $event->id, 'realm_id' => $event->realm_id]));
    }

    /**
     * Handle the Event "restored" event.
     */
    public function restored(Event $event): void
    {
        event(new ItemCreated('e', $event));
    }

    /**
     * Handle the Event "force deleted" event.
     */
    public function forceDeleted(Event $event): void
    {
        event(new ItemDeleted('e', (object) ['id' => $event->id, 'realm_id' => $event->realm_id]));
    }
}
