<?php

namespace App\Livewire;

use App\Events\SecurityAuditEvent;
use App\Livewire\Forms\EventForm;
use App\Livewire\Traits\TrimStringsAndConvertEmptyStringsToNull;
use App\Models\Event;
use App\Models\Menu;
use App\Models\Schedule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

class UpdateEvent extends Component
{
    use TrimStringsAndConvertEmptyStringsToNull;

    public EventForm $form;

    #[Locked]
    public $avUpdating = false;

    public function mount(Event $event, $avUpdating)
    {
        if ($avUpdating && ! auth()->check()) {
            $this->authorize('avUpdate', $event);
        } else {
            $this->authorize('update', $event);
        }
        $this->form->setEvent($event);
        $this->avUpdating = $avUpdating && ! auth()->check();
    }

    #[Computed]
    public function duration()
    {
        return Schedule::calculateDuration($this->form->start, $this->form->end, $this->form->start_time, $this->form->end_time, $this->form->repeat);
    }

    public function save()
    {
        if ($this->avUpdating) {
            $this->authorize('avUpdate', $this->form->event);
        } else {
            $this->authorize('update', $this->form->event);
        }
        $res = $this->form->store($this->avUpdating);
        if ($res === null) { // Store usually returns void, check logic? The original used $res
            // If store() returns nothing, we assume success if no exception
            $res = true;
        }

        if ($res) {
            flash(__('The event has been updated'))->success();
            if ($this->avUpdating) {
                $this->redirectRoute('events.avedit', ['event' => $this->form->event->id, 'api_token' => $this->form->event->api_token]);
            } else {
                // Only redirect if the user is not AV
                $this->redirectRoute('events.index');
            }
        }
    }

    public function removeApiToken()
    {
        $this->authorize('update', $this->form->event);
        $this->form->event->removeApiToken();
        event(new SecurityAuditEvent(
            action: 'event.api_token_removed',
            description: "API token removed for Event ID: {$this->form->event->id}",
            userId: auth()->id(),
            realmId: $this->form->event->realm_id
        ));
    }

    public function refreshApiToken()
    {
        $this->authorize('update', $this->form->event);
        $this->form->event->updateApiToken();
        event(new SecurityAuditEvent(
            action: 'event.api_token_refreshed',
            description: "API token refreshed for Event ID: {$this->form->event->id}",
            userId: auth()->id(),
            realmId: $this->form->event->realm_id
        ));
    }

    public function deleteEvent()
    {
        $this->authorize('delete', $this->form->event);

        $event = $this->form->event;
        $this->form->reset();

        DB::transaction(function () use ($event) {
            $event->menus()->detach();
            $event->delete();
        });

        event(new SecurityAuditEvent(
            action: 'event.deleted',
            description: "Event deleted: '{$event->name}' (ID: {$event->id})",
            userId: auth()->id(),
            realmId: $event->realm_id
        ));

        flash(__('Event deleted'))->success();
        Log::channel('crud')->warning(__('Event deleted'), [
            'user' => auth()->id(),
        ]);

        return redirect()->route('events.index');
    }

    public function render()
    {
        return view(
            'livewire.edit-event',
            [
                'allMenus' => Menu::ofRealm($this->form->event->realm_id)->select(['id', 'name'])->orderBy('name')->get(),
                'deleteButton' => 'deleteEvent',
                'event' => $this->form->event, // maybe bug because of this?
            ]
        );
    }
}
