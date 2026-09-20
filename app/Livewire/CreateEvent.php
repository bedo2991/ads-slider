<?php

namespace App\Livewire;

use App\Livewire\Forms\EventForm;
use App\Livewire\Traits\TrimStringsAndConvertEmptyStringsToNull;
use App\Models\Event;
use App\Models\Menu;
use App\Models\Schedule;
use Livewire\Attributes\Computed;
use Livewire\Component;

class CreateEvent extends Component
{
    use TrimStringsAndConvertEmptyStringsToNull;

    public EventForm $form;

    private $allTemplates;

    private $template;

    public function save()
    {
        $this->authorize('create', Event::class);
        $res = $this->form->store();
        if ($res) {
            flash(__('The event has been created'))->success();
            $this->redirectRoute('events.index');
        }
    }

    public function mount($allTemplates, $template)
    {
        $this->allTemplates = $allTemplates;
        if ($template) {
            $this->form->setTemplate($template);
        }
    }

    #[Computed]
    public function duration()
    {
        return Schedule::calculateDuration($this->form->start, $this->form->end, $this->form->start_time, $this->form->end_time, $this->form->repeat);
    }

    public function render()
    {
        return view(
            'livewire.edit-event',
            [
                'allTemplates' => $this->allTemplates,
                'allMenus' => Menu::ofRealm(auth()->user()->realm_id)->select(['id', 'name'])->orderBy('name')->get(),
            ]
        );
    }
}
