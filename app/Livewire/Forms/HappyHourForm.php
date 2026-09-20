<?php

namespace App\Livewire\Forms;

use App\Livewire\Traits\ErrorBanner;
use App\Models\Event;
use App\Models\HappyHour;
use Livewire\Attributes\Validate;
use Livewire\Form;

class HappyHourForm extends Form
{
    use ErrorBanner;

    public ?Event $event = null;

    #[Validate('required|max:20')]
    public $drink = '';

    #[Validate('required|max:20')]
    public $price = '';

    #[Validate('nullable|max:40')]
    public $info = '';

    #[Validate('date|required')]
    public $start;

    #[Validate('date|after:start|required')]
    public $end;

    public function setParentEvent(Event $event)
    {
        $this->event = $event;
        if ($event->happy_hour) {
            $this->setHappyHour($event->happy_hour);
        }
    }

    private function setHappyHour(HappyHour $hh)
    {
        $this->drink = $hh->drink;
        $this->price = $hh->price;
        $this->info = $hh->info;
        $this->start = $hh->start;
        $this->end = $hh->end;
    }

    public function deleteHappyHour()
    {
        $this->event->happy_hour->delete();
        $this->event->happy_hour = null;
        $this->drink = '';
        $this->price = '';
        $this->info = '';
        $this->start = '';
        $this->end = '';
    }

    public function saveHappyHour()
    {
        $this->validate();
        $this->event->happy_hour()->updateOrCreate(['event_id' => $this->event->id], [
            'drink' => $this->drink,
            'price' => $this->price,
            'info' => $this->info,
            'start' => $this->start,
            'end' => $this->end,
            'event_id' => $this->event->id,
        ]);
    }
}
