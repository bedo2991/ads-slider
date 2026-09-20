<?php

namespace App\Livewire\Forms;

use App\Livewire\Traits\ErrorBanner;
use App\Models\Menu;
use App\Models\Template;
use Livewire\Attributes\Validate;
use Livewire\Form;

class TemplateForm extends Form
{
    use ErrorBanner;

    public ?Template $template = null;

    #[Validate('required|max:191')]
    public $name = '';

    #[Validate('required|date_format:H:i')]
    public $start_time = '';

    #[Validate('required|date_format:H:i')]
    public $end_time = '';

    #[Validate('max:191')]
    public $place;

    #[Validate('max:7')]
    public $color = '#FF0000';

    #[Validate('max:1852|nullable')]
    public $link;

    #[Validate('nullable')]
    public $icon;

    #[Validate('max:150|nullable')]
    public $marquee;

    #[Validate('max:60|integer|numeric|nullable')]
    public $preparation_time;

    #[Validate('boolean')]
    public $final_round_confirmed = true;

    #[Validate('boolean')]
    public $is_karaoke = false;

    #[Validate('boolean')]
    public $not_closing = false;

    #[Validate('nullable')] // Add validation for repeat
    public $repeat;

    public $menus = [];

    public function setTemplate(?Template $t = null)
    {
        if (! $t || ! $t->exists) {
            return;
        }
        $this->template = $t;
        $this->name = $t->name;
        // Load from schedule if available (migrated data), else fallback to model (legacy/transient)
        $this->start_time = $t->schedule?->start_time ? substr($t->schedule->start_time, 0, 5) : null;
        $this->end_time = $t->schedule?->end_time ? substr($t->schedule->end_time, 0, 5) : null;
        $this->repeat = $t->schedule?->repeat;

        $this->place = $t->place;
        $this->color = $t->color;
        $this->link = $t->link;
        $this->icon = $t->icon;
        $this->marquee = $t->marquee;
        $this->preparation_time = $t->preparation_time;
        $this->final_round_confirmed = $t->final_round_confirmed;
        $this->is_karaoke = $t->is_karaoke;
        $this->not_closing = $t->not_closing;
        $this->menus = $t->menus->pluck('id')->toArray();
    }

    public function deleteTemplate()
    {
        $this->template->menus()->detach();
        // Delete schedule
        $this->template->schedule()->delete();
        $this->template->delete();
    }

    public function storeTemplate()
    {
        $validated = $this->validate();

        // Extract schedule fields
        $scheduleData = [
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'repeat' => $validated['repeat'] ?? null,
            'realm_id' => auth()->user()->realm_id,
            'user_id' => auth()->id(),
        ];

        // Remove schedule fields from template data
        unset($validated['start_time'], $validated['end_time'], $validated['repeat']);

        if (! $this->template) {
            $this->template = new Template;
            $this->template->realm_id = auth()->user()->realm_id;
        }

        $this->template->fill($validated);

        $this->template->user_id = auth()->id();
        $this->template->save();

        $validMenuIds = Menu::ofRealm(auth()->user()->realm_id)
            ->whereIn('id', (array) $this->menus)
            ->pluck('id');
        $this->template->menus()->sync($validMenuIds);

        // Update or Create Schedule
        $this->template->schedule()->updateOrCreate(
            [], // Match attributes (none, we want to update the one related record)
            $scheduleData
        );
    }
}
