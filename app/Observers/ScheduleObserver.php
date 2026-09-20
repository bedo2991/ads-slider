<?php

namespace App\Observers;

use App\Models\Schedule;
use App\Providers\ItemCreated;
use App\Providers\ItemDeleted;
use App\Providers\ItemUpdated;

class ScheduleObserver
{
    /**
     * Handle the Schedule "created" event.
     */
    public function created(Schedule $schedule): void
    {
        $type = $this->getType($schedule);
        if ($type) {
            event(new ItemCreated($type, $schedule));
        }
    }

    /**
     * Handle the Schedule "updated" event.
     */
    public function updated(Schedule $schedule): void
    {
        $type = $this->getType($schedule);
        if ($type) {
            event(new ItemUpdated($type, $schedule));
        }
    }

    /**
     * Handle the Schedule "deleted" event.
     */
    public function deleted(Schedule $schedule): void
    {
        $type = $this->getType($schedule);
        if ($type) {
            event(new ItemDeleted($type, (object) ['id' => $schedule->id, 'realm_id' => $schedule->realm_id]));
        }
    }

    /**
     * Handle the Schedule "restored" event.
     */
    public function restored(Schedule $schedule): void
    {
        //
    }

    /**
     * Handle the Schedule "force deleted" event.
     */
    public function forceDeleted(Schedule $schedule): void
    {
        //
    }

    private function getType(Schedule $schedule): ?string
    {
        return match ($schedule->scheduleable_type) {
            'VI' => 'vs',
            'PI' => 'ps',
            'EV' => 'es',
            // 'EV' is handled by EventObserver? But Event model is modified?
            // Wait, Event model is now "scheduleable" but also exists as Event model?
            // The refactor says Events are also Schedules.
            // If I update an Event via Event model, does it touch Schedule?
            // Or do we update Schedule model directly?
            // The Events have their own table triggers usually?
            // But here we are talking about 'PictureSlide' replaced by Schedule.
            // So definitely need 'vs' and 'ps'.
            // 'EV' might still have its own observer if `Event::observe` is used.
            // But if Event model extends Model, checks `events` table.

            // Assuming for now only PI and VI need 'ps' and 'vs' prefixes for legacy frontend listeners.
            default => null,
        };
    }
}
