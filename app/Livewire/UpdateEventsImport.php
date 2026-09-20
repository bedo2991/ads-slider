<?php

namespace App\Livewire;

use App\Events\SecurityAuditEvent;
use App\Livewire\Forms\EventsImportForm;
use App\Livewire\Traits\TrimStringsAndConvertEmptyStringsToNull;
use App\Models\EventsImport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class UpdateEventsImport extends Component
{
    use TrimStringsAndConvertEmptyStringsToNull;

    public EventsImportForm $form;

    public function mount(EventsImport $eventsImport)
    {
        $this->authorize('update', $eventsImport);
        $this->form->setEventsImport($eventsImport);
    }

    public function save()
    {
        $this->authorize('update', $this->form->eventsImport);

        $res = $this->form->update();
        if ($res) {
            flash(__('The events import has been updated'))->success();
            $this->redirectRoute('eventsImports.index');
        }
    }

    public function deleteEventsImport()
    {
        $this->authorize('delete', $this->form->eventsImport);

        $this->form->eventsImport->delete();
        flash(__('Events import deleted'))->success();

        event(new SecurityAuditEvent(
            action: 'events_import.deleted',
            description: "Events import '{$this->form->eventsImport->import_name}' (ID: {$this->form->eventsImport->id}) deleted by user ID: ".Auth::id(),
            userId: Auth::id(),
            realmId: $this->form->eventsImport->realm_id,
            context: ['events_import_id' => $this->form->eventsImport->id, 'events_import_name' => $this->form->eventsImport->import_name]
        ));

        Log::channel('crud')->warning('Events import deleted', [
            'eventsImport' => $this->form->eventsImport,
            'user' => auth()->id(),
        ]);
        $this->redirectRoute('eventsImports.index');
    }

    public function render()
    {
        return view('livewire.edit-events-import', [
            'deleteButton' => 'deleteEventsImport',
            // 'allMenus' => Menu::all(['id', 'name'])
        ]);
    }
}
