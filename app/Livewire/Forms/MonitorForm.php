<?php

namespace App\Livewire\Forms;

use App\Livewire\Traits\ErrorBanner;
use App\Models\Monitor;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Form;

class MonitorForm extends Form
{
    use ErrorBanner;

    public ?Monitor $monitor = null;

    #[Validate('required|max:50')]
    public $name = '';

    #[Validate('required|integer|between:1,15')]
    public $events_to_show = 8;

    #[Validate('string|max:10|nullable')]
    public $locale = '';

    #[Validate('boolean')]
    public $show_preparation_countdowns = true;

    #[Validate('boolean')]
    public $show_final_rounds = true;

    #[Validate('boolean')]
    public $show_we_are_closing = true;

    #[Validate('boolean')]
    public $show_we_are_closed_marketing = true;

    #[Validate('boolean')]
    public $show_cancelled_events = true;

    #[Validate('boolean')]
    public $show_menus = true;

    #[Validate('boolean')]
    public $show_happy_hours = true;

    #[Validate('boolean')]
    public $show_pictures = true;

    #[Validate('boolean')]
    public $show_videos = true;

    #[Validate('boolean')]
    public $show_karaoke = false;

    #[Validate('boolean')]
    public $show_weather_forecast = true;

    #[Validate('boolean')]
    public $use_animations = true;

    #[Validate('boolean')]
    public $show_marquee = false;

    #[Validate('boolean')]
    public $show_event_while_is_happening = false;

    #[Validate('boolean')]
    public $show_orderslist = false;

    public function saveMonitor()
    {
        $validated = $this->validate();
        if (! $this->monitor) {
            $this->monitor = new Monitor;
            $this->monitor->api_token = Str::random(80);
            $this->monitor->realm_id = Auth::user()->realm_id;
        }

        $realm = $this->monitor->realm ?? Auth::user()->realm;
        if (empty($realm?->ow_api_key)) {
            $this->show_weather_forecast = false;
            $validated['show_weather_forecast'] = false;
        }

        $this->monitor->fill($validated);
        $this->monitor->user_id = Auth::id();
        $this->monitor->save();
    }

    public function setMonitor(?Monitor $m)
    {
        if (! $m || ! $m->exists) {
            return;
        }
        $this->monitor = $m;
        $this->name = $m->name;
        $this->events_to_show = $m->events_to_show;
        $this->show_preparation_countdowns = $m->show_preparation_countdowns;
        $this->show_final_rounds = $m->show_final_rounds;
        $this->show_we_are_closing = $m->show_we_are_closing;
        $this->show_we_are_closed_marketing = $m->show_we_are_closed_marketing;
        $this->show_cancelled_events = $m->show_cancelled_events;
        $this->show_menus = $m->show_menus;
        $this->show_happy_hours = $m->show_happy_hours;
        $this->show_pictures = $m->show_pictures;
        $this->show_videos = $m->show_videos;
        $this->show_karaoke = $m->show_karaoke;
        $this->show_weather_forecast = $m->show_weather_forecast;
        $this->use_animations = $m->use_animations;
        $this->show_marquee = $m->show_marquee;
        $this->show_event_while_is_happening = $m->show_event_while_is_happening;
        $this->show_orderslist = $m->show_orderslist;
        $this->locale = $m->locale;
    }
}
