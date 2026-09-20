<?php

namespace App\Livewire\Forms;

use App\Livewire\Traits\ErrorBanner;
use App\Models\EventsImport;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Validate;
use Livewire\Form;

class EventsImportForm extends Form
{
    use ErrorBanner;

    public ?EventsImport $eventsImport;

    #[Validate('required|max:191')]
    public $import_name = '';

    #[Validate('url|max:1852')]
    public $import_url = '';

    #[Validate('boolean')]
    public $import_disabled = false;

    #[Validate('max:191')]
    public $place;

    #[Validate('max:7')]
    public $color = '#FF0000';

    #[Validate('max:1852|nullable')]
    public $link;

    #[Validate('nullable')]
    public $icon = 'star';

    #[Validate('max:150|nullable')]
    public $marquee;

    #[Validate('max:60|integer|numeric|nullable')]
    public $preparation_time;

    // TODO: maybe support Menus?

    #[Validate('boolean|nullable')]
    public $disabled = false;

    #[Validate('boolean|nullable')]
    public $final_round_confirmed = true;

    #[Validate('boolean|nullable')]
    public $is_karaoke = false;

    #[Validate('boolean|nullable')]
    public $not_closing = false;

    public function setEventsImport(EventsImport $eventsImport)
    {
        $this->eventsImport = $eventsImport;
        $this->import_name = $eventsImport->import_name;
        $this->import_url = $eventsImport->import_url;
        $this->import_disabled = $eventsImport->import_disabled;
        $this->place = $eventsImport->place;
        $this->color = $eventsImport->color;
        $this->link = $eventsImport->link;
        $this->icon = $eventsImport->icon;
        $this->marquee = $eventsImport->marquee;
        $this->preparation_time = $eventsImport->preparation_time;
        $this->disabled = $eventsImport->disabled;
        $this->final_round_confirmed = $eventsImport->final_round_confirmed;
        $this->is_karaoke = $eventsImport->is_karaoke;
        $this->not_closing = $eventsImport->not_closing;
    }

    public function update(): bool
    {
        $validated = $this->validate();

        $this->eventsImport->fill($validated);
        $this->eventsImport->user_id = auth()->id();

        try {
            $this->eventsImport->save();
            Log::channel('crud')->info('Events import updated', [
                'eventsImport' => $this->eventsImport,
                'user' => auth()->id(),
            ]);
        } catch (\Exception $e) {
            $this->setErrorMessage($e->getMessage());
            Log::error($e->getMessage());

            return false;
        }

        return true;
    }

    public function store(): bool
    {
        $validated = $this->validate();

        $eventsImport = new EventsImport;

        $eventsImport->fill($validated);

        $eventsImport->realm_id = auth()->user()->realm_id;
        $eventsImport->user_id = auth()->id();

        try {
            $eventsImport->save();
            Log::channel('crud')->info('Events import created', [
                'eventsImport' => $eventsImport,
                'user' => auth()->id(),
            ]);
        } catch (\Exception $e) {
            $this->setErrorMessage($e->getMessage());
            Log::error($e->getMessage());

            return false;
        }

        return true;
    }

    public function resetForm()
    {
        $this->reset();
    }
}
