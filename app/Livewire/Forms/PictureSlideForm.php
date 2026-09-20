<?php

namespace App\Livewire\Forms;

use App\Livewire\Traits\ErrorBanner;
use App\Models\Picture;
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Validate;
use Livewire\Form;

class PictureSlideForm extends Form
{
    use ErrorBanner;

    public ?Schedule $schedule = null;

    #[Validate('required|date_format:H:i')]
    public $start_time = '';

    #[Validate('required|date_format:H:i')]
    public $end_time = '';

    #[Validate('present|date|nullable|required_with:end')]
    public $start = null;

    #[Validate('present|required_with:start|date|after_or_equal:start')]
    public $end = null;

    #[Validate('present|max:1234567|nullable|required_without:start,end|integer|not_regex:/([^1-7])/|not_regex:/(.).*\1/')]
    public $repeat = null;

    #[Validate('boolean')]
    public $disabled = false;

    #[Validate('required|integer')]
    public $picture_id = '';

    public function setPictureId($id)
    {
        $this->picture_id = $id;
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
        $this->picture_id = $schedule->scheduleable_id;
    }

    public function delete()
    {
        $logContext = [
            'id' => $this->schedule->id,
            'picture_id' => $this->schedule->scheduleable_id,
            'picture_name' => $this->schedule->scheduleable?->name,
            'user' => auth()->id(),
        ];

        $this->schedule->delete();

        flash(__('The picture slide has been deleted'))->success();
        Log::channel('crud')->warning('Schedule (Picture) deleted', $logContext);
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

        $this->schedule->scheduleable_type = 'PI'; // Use MorphMap code
        $this->schedule->scheduleable_id = $validated['picture_id'];

        // Get realm_id from picture
        $picture = Picture::ofRealm(auth()->user()->realm_id)->findOrFail($validated['picture_id']);
        $this->schedule->realm_id = auth()->user()->realm_id;
        $this->schedule->user_id = auth()->id();
        $this->schedule->save();
    }
}
