<?php

namespace App\Livewire;

use App\Livewire\Forms\EventsImportForm;
use App\Livewire\Traits\TrimStringsAndConvertEmptyStringsToNull;
use Livewire\Component;

class CreateEventsImport extends Component
{
    use TrimStringsAndConvertEmptyStringsToNull;

    public EventsImportForm $form;

    public function save()
    {
        $this->authorize('create', EventsImport::class);
        $res = $this->form->store();
        if ($res) {
            flash(__('The events import has been created'))->success();
            $this->redirectRoute('eventsImports.index');
        }
    }

    public function render()
    {
        return view('livewire.edit-events-import');
    }
}
