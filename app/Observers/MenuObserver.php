<?php

namespace App\Observers;

use App\Models\Menu;
use App\Providers\ItemCreated;
use App\Providers\ItemDeleted;
use App\Providers\ItemUpdated;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class MenuObserver implements ShouldHandleEventsAfterCommit
{
    /**
     * Handle the Monitor "created" event.
     */
    public function created(Menu $menu): void
    {
        event(new ItemCreated('menu', $menu));
    }

    /**
     * Handle the Monitor "updated" event.
     */
    public function updated(Menu $menu): void
    {
        event(new ItemUpdated('menu', $menu->json_content));
    }

    /**
     * Handle the Monitor "deleted" event.
     */
    public function deleted(Menu $menu): void
    {
        event(new ItemDeleted('menu', (object) ['id' => $menu->id, 'realm_id' => $menu->realm_id]));
    }

    /**
     * Handle the Monitor "restored" event.
     */
    public function restored(Menu $menu): void
    {
        event(new ItemCreated('menu', $menu));
    }

    /**
     * Handle the Monitor "force deleted" event.
     */
    public function forceDeleted(Menu $menu): void
    {
        event(new ItemDeleted('menu', (object) ['id' => $menu->id, 'realm_id' => $menu->realm_id]));
    }
}
