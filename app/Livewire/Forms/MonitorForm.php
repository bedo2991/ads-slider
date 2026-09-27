<?php

namespace App\Livewire\Forms;

use App\Enums\ScheduledSlideType;
use App\Livewire\Traits\ErrorBanner;
use App\Models\Monitor;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
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

    #[Validate('nullable|string')]
    public $marketing_sentences = '';

    #[Validate('nullable|string')]
    public $schedule = '';

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

        if ($this->marketing_sentences) {
            $lines = array_values(array_filter(array_map('trim', explode("\n", (string) $this->marketing_sentences)), fn ($s) => $s !== ''));
            $validated['marketing_sentences'] = ! empty($lines) ? $lines : null;
        } else {
            $validated['marketing_sentences'] = null;
        }

        if ($this->schedule) {
            $scheduleItems = ScheduledSlideType::parseSchedule($this->schedule);
            if (! empty($scheduleItems)) {
                $invalid = ScheduledSlideType::validateSchedule($scheduleItems);
                if (! empty($invalid)) {
                    throw ValidationException::withMessages([
                        'form.schedule' => __('Invalid slide type(s): :types. Valid types are: :valid', [
                            'types' => implode(', ', $invalid),
                            'valid' => implode(', ', ScheduledSlideType::values()),
                        ]),
                    ]);
                }
                $validated['schedule'] = $scheduleItems;
            } else {
                $validated['schedule'] = null;
            }
        } else {
            $validated['schedule'] = null;
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
        $this->show_preparation_countdowns = (bool) $m->show_preparation_countdowns;
        $this->show_final_rounds = (bool) $m->show_final_rounds;
        $this->show_we_are_closing = (bool) $m->show_we_are_closing;
        $this->show_we_are_closed_marketing = (bool) $m->show_we_are_closed_marketing;
        $this->marketing_sentences = is_array($m->marketing_sentences) ? implode("\n", $m->marketing_sentences) : '';
        $this->schedule = is_array($m->schedule) ? implode("\n", $m->schedule) : '';
        $this->show_cancelled_events = (bool) $m->show_cancelled_events;
        $this->show_menus = (bool) $m->show_menus;
        $this->show_happy_hours = (bool) $m->show_happy_hours;
        $this->show_pictures = (bool) $m->show_pictures;
        $this->show_videos = (bool) $m->show_videos;
        $this->show_karaoke = (bool) $m->show_karaoke;
        $this->show_weather_forecast = (bool) $m->show_weather_forecast;
        $this->use_animations = (bool) $m->use_animations;
        $this->show_marquee = (bool) $m->show_marquee;
        $this->show_event_while_is_happening = (bool) $m->show_event_while_is_happening;
        $this->show_orderslist = (bool) $m->show_orderslist;
        $this->locale = $m->locale;
    }
}
