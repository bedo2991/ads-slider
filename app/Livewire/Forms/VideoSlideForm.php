<?php

namespace App\Livewire\Forms;

use App\Livewire\Traits\ErrorBanner;
use App\Models\Schedule;
use App\Models\Video;
use Carbon\Carbon;
use Livewire\Attributes\Validate;
use Livewire\Form;

class VideoSlideForm extends Form
{
    use ErrorBanner;

    public ?Schedule $schedule = null;

    #[Validate('required|date_format:H:i')]
    public $start_time = '';

    #[Validate('required|date_format:H:i')]
    public $end_time = '';

    #[Validate('present|date|nullable|required_with:end')]
    public $start = null;

    #[Validate('present|required_with:start|date|nullable|after_or_equal:start')]
    public $end = null;

    #[Validate('present|max:1234567|nullable|required_without:start,end|integer|not_regex:/([^1-7])/|not_regex:/(.).*\1/')]
    public $repeat = '';

    #[Validate('boolean')]
    public $disabled = false;

    #[Validate('required|integer')]
    public $video_id = '';

    public function setVideoId($id)
    {
        $this->video_id = $id;
    }

    public function setSchedule(Schedule $schedule)
    {
        if (! $schedule->exists) {
            return;
        }
        $this->schedule = $schedule;
        $this->start_time = Carbon::parse($schedule->start_time)->format('H:i');
        $this->end_time = Carbon::parse($schedule->end_time)->format('H:i');
        $this->start = $schedule->start?->toDateString();
        $this->end = $schedule->end?->toDateString();
        $this->repeat = $schedule->repeat;
        $this->disabled = $schedule->disabled;
        $this->video_id = $schedule->scheduleable_id;
    }

    public function delete()
    {
        $this->schedule->delete();
    }

    public function store()
    {
        // Set default values if empty
        if (empty($this->start_time)) {
            $this->start_time = '00:00';
        }

        if (empty($this->end_time)) {
            $this->end_time = '23:59';
        }

        if (empty($this->start)) {
            $this->start = now()->yesterday()->toDateString();
        }

        if (empty($this->end)) {
            $this->end = '2100-12-31';
        }
        $validated = $this->validate();

        if (! $this->schedule) {
            $this->schedule = new Schedule;
        }

        $this->schedule->fill([
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'start' => $validated['start'],
            'end' => $validated['end'],
            'repeat' => $validated['repeat'],
            'disabled' => $validated['disabled'] ?? false,
        ]);

        $this->schedule->scheduleable_type = 'VI'; // Use MorphMap code
        $this->schedule->scheduleable_id = $validated['video_id'];

        $video = Video::ofRealm(auth()->user()->realm_id)->findOrFail($validated['video_id']);

        $this->schedule->realm_id = auth()->user()->realm_id;
        $this->schedule->user_id = auth()->id();
        $this->schedule->save();
    }
}
