<?php

namespace App\Livewire;

use App\Events\SecurityAuditEvent;
use App\Livewire\Forms\HappyHourForm;
use App\Livewire\Traits\TrimStringsAndConvertEmptyStringsToNull;
use App\Models\Event;
use App\Models\HappyHour;
use Livewire\Component;

class EditHappyHour extends Component
{
    use TrimStringsAndConvertEmptyStringsToNull;

    public HappyHourForm $hhForm;

    public bool $isManagerUpdating = false;

    public function mount(Event $event, bool $isManagerUpdating): void
    {
        $this->hhForm->setParentEvent($event);
        $this->isManagerUpdating = $isManagerUpdating;
    }

    public function createHappyHour()
    {
        $this->authorize('create', [HappyHour::class, $this->hhForm->event]);
        $this->hhForm->saveHappyHour();
        $this->hhForm->setSuccessMessage('Happy hour created', false);
    }

    public function createHappyHourAsManager()
    {
        $this->authorize('createAsManager', [HappyHour::class, $this->hhForm->event]);
        $this->hhForm->saveHappyHour();
        $this->hhForm->setSuccessMessage('Happy hour created', false);
    }

    public function updateHappyHour()
    {
        $this->authorize('update', $this->hhForm->event->happy_hour);
        $this->hhForm->saveHappyHour();
        $this->hhForm->setSuccessMessage('Happy hour updated', false);
    }

    public function updateHappyHourAsManager()
    {
        $this->authorize('updateAsManager', [$this->hhForm->event->happy_hour, $this->hhForm->event]);
        $this->hhForm->saveHappyHour();
        $this->hhForm->setSuccessMessage('Happy hour updated', false);
    }

    public function deleteHappyHour()
    {
        $this->authorize('delete', $this->hhForm->event->happy_hour);
        $hh = $this->hhForm->event->happy_hour;
        $eventName = $this->hhForm->event->name;
        $this->hhForm->deleteHappyHour();
        event(
            new SecurityAuditEvent(
                action: 'happy_hour.deleted',
                description: "Happy hour '{$hh->name}' (ID: {$hh->id}) of event '{$eventName}' deleted by user ID: ".auth()->id(),
                userId: auth()->id(),
                realmId: $hh->realm_id,
                context: ['happy_hour_id' => $hh->id, 'name' => $hh->name]
            )
        );
        $this->hhForm->setSuccessMessage('Happy hour deleted', false);
    }

    public function deleteHappyHourAsManager()
    {
        $this->authorize('deleteAsManager', [$this->hhForm->event->happy_hour, $this->hhForm->event]);
        $this->hhForm->deleteHappyHour();
        $this->hhForm->setSuccessMessage('Happy hour deleted', false);
    }

    public function render()
    {
        return view('livewire.edit-happy-hour', [
            'deleteButton' => 'deleteHappyHour'.($this->isManagerUpdating == true ? 'AsManager' : ''),
        ]);
    }
}
