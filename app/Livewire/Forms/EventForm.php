<?php

namespace App\Livewire\Forms;

use App\Livewire\Traits\ErrorBanner;
use App\Models\Event;
use App\Models\Menu;
use App\Models\Template;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Form;

class EventForm extends Form
{
    use ErrorBanner;

    public ?Event $event = null;

    #[Validate('required|max:191')]
    public $name = '';

    #[Validate('required|date_format:H:i')]
    public $start_time = '';

    #[Validate('required|date_format:H:i')]
    public $end_time = '';

    #[Validate('present|max:1234567|nullable|required_without:start,end|integer|not_regex:/([^1-7])/|not_regex:/(.).*\1/')]
    public $repeat = '';

    #[Validate('present|date|nullable|required_with:end')]
    public $start;

    #[Validate('present|required_with:start|date|nullable|after_or_equal:start')]
    public $end;

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

    public $menus;

    #[Validate('boolean')]
    public $disabled = false;

    #[Validate('boolean')]
    public $cancelled = false;

    #[Validate('boolean')]
    public $final_round_confirmed = true;

    #[Validate('boolean')]
    public $is_karaoke = false;

    #[Validate('boolean')]
    public $is_protected = false;

    #[Validate('boolean')]
    public $not_closing = false;

    public function setTemplate(Template $t)
    {
        $this->name = $t->name;
        // Use schedule data if available
        $this->start_time = $t->schedule?->start_time ? substr($t->schedule->start_time, 0, 5) : substr($t->start_time, 0, 5);
        $this->end_time = $t->schedule?->end_time ? substr($t->schedule->end_time, 0, 5) : substr($t->end_time, 0, 5);
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

    public function setEvent(Event $event)
    {
        $this->event = $event;
        $this->name = $event->name;
        // Prioritize Schedule data
        $this->start_time = $event->schedule?->start_time ? substr($event->schedule->start_time, 0, 5) : null;
        $this->end_time = $event->schedule?->end_time ? substr($event->schedule->end_time, 0, 5) : null;
        $this->repeat = $event->schedule?->repeat;
        $this->start = $event->schedule?->start ? $event->schedule->start->format('Y-m-d') : null;
        $this->end = $event->schedule?->end ? $event->schedule->end->format('Y-m-d') : null;

        $this->place = $event->place;
        $this->color = $event->color;
        $this->link = $event->link;
        $this->icon = $event->icon;
        $this->marquee = $event->marquee;
        $this->preparation_time = $event->preparation_time;
        $this->disabled = (bool) ($event->schedule?->disabled ?? false);
        $this->cancelled = (bool) ($event->cancelled ?? false);
        $this->final_round_confirmed = (bool) ($event->final_round_confirmed ?? true);
        $this->is_karaoke = (bool) ($event->is_karaoke ?? false);
        $this->is_protected = (bool) ($event->is_protected ?? false);
        $this->not_closing = (bool) ($event->not_closing ?? false);
        $this->menus = $event->menus->pluck('id')->toArray();
    }

    public function deleteEvent()
    {
        try {
            DB::beginTransaction();
            $this->event->menus()->detach();
            $this->event->schedule()->delete(); // Delete schedule
            $this->event->delete();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            $this->setErrorMessage('The event could not be deleted. Please report this error.');
            Log::error('Could not delete event', [
                'event' => $this->event,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function store($avUpdating = false)
    {
        $this->clearMessage();
        $validated = $this->validate();

        $scheduleData = [
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'start' => $this->start,
            'end' => $this->end,
            'repeat' => $this->repeat,
            'return_null_if_empty' => true, // Helper flag? No, just checking logic
            'disabled' => $this->disabled,
            'realm_id' => $this->event->realm_id ?? Auth::user()->realm_id,
            // user_id handled below for Event, Schedule also needs it?
            'user_id' => $this->event->user_id ?? Auth::id(),
        ];

        if (! $this->event) {
            $this->event = new Event;
            $this->event->realm_id = Auth::user()->realm_id;
        }

        $this->event->fill($validated);

        if (! $avUpdating) {
            $this->event->user_id = Auth::id();
            $scheduleData['user_id'] = Auth::id();
        } else {
            $scheduleData['user_id'] = $this->event->user_id;
        }

        // ... existing save logic ...

        // Initialize the api_token, if empty upon creation
        if (empty($this->event->api_token)) {
            $this->event->api_token = Str::random(80);
        }
        try {
            DB::beginTransaction();
            $this->event->save();
            // Save the connected menus

            $validMenuIds = Menu::ofRealm($this->event->realm_id)
                ->whereIn('id', (array) $this->menus)
                ->pluck('id');
            $this->event->menus()->sync($validMenuIds);

            // Sanitize scheduleData (remove helper keys if any)
            unset($scheduleData['return_null_if_empty']);

            // Save Schedule
            $this->event->schedule()->updateOrCreate(
                [], // Match by relationship
                $scheduleData
            );

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            $this->setErrorMessage('The event could not be stored in the DB, please report this error.');
            Log::error('Event creation failed', [
                'event' => $this->event,
                'user' => $avUpdating ? 'AV' : Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return false;
        }

        if ($avUpdating) {
            Log::channel('crud')->info('Event stored', [
                'event' => $this->event,
                'user' => 'AV update with token '.$this->event->api_token,
            ]);
        } else {
            Log::channel('crud')->info('Event stored', [
                'event' => $this->event,
                'user' => Auth::id(),
            ]);
        }

        return true;
    }
}
