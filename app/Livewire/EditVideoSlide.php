<?php

namespace App\Livewire;

use App\Events\SecurityAuditEvent;
use App\Livewire\Forms\VideoSlideForm;
use App\Livewire\Traits\TrimStringsAndConvertEmptyStringsToNull;
use App\Models\Schedule;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class EditVideoSlide extends Component
{
    use TrimStringsAndConvertEmptyStringsToNull;

    public VideoSlideForm $form;

    public $allVideos = [];

    public function createVideoSlide()
    {
        $this->authorize('create', Schedule::class);
        $this->form->store();
        flash(__('The video slide has been created'))->success();
        Log::channel('crud')->info('VideoSlide created', [
            'videoSlide' => $this->form->schedule,
            'user' => auth()->id(),
        ]);
        $this->redirectRoute('vidSlides.index');
    }

    public function updateVideoSlide()
    {
        $this->authorize('update', $this->form->schedule);
        $this->form->store();
        flash(__('The video slide has been updated'))->success();
        Log::channel('crud')->info('VideoSlide updated', [
            'videoSlide' => $this->form->schedule,
            'user' => auth()->id(),
        ]);
        $this->redirectRoute('vidSlides.index');
    }

    public function deleteVideoSlide()
    {
        $this->authorize('delete', $this->form->schedule);
        $schedule = $this->form->schedule;

        $this->form->delete();

        event(new SecurityAuditEvent(
            action: 'schedule.deleted',
            description: "Video slide schedule (ID: {$schedule->id}) deleted for Video ID: {$schedule->scheduleable_id}",
            userId: auth()->id(),
            realmId: $schedule->realm_id,
            context: [
                'schedule_id' => $schedule->id,
                'video_id' => $schedule->scheduleable_id,
                'type' => 'VI',
            ]
        ));

        flash(__('The video slide has been deleted'))->success();
        Log::channel('crud')->warning('VideoSlide deleted', [
            'id' => $schedule->id,
            'video_id' => $schedule->scheduleable_id,
            'user' => auth()->id(),
        ]);

        return redirect()->route('vidSlides.index');
    }

    public function mount($allVideos, ?Schedule $vidSlide = null, $selected_video = null)
    {
        if ($vidSlide && $vidSlide->exists) {
            $this->authorize('update', $vidSlide);
        } else {
            $this->authorize('create', Schedule::class);
        }
        $this->form->setVideoId($selected_video);
        $this->allVideos = $allVideos;
        if ($vidSlide && $vidSlide->exists) {
            $this->form->setSchedule($vidSlide);
        }
    }

    public function render()
    {
        return view('livewire.edit-video-slide');
    }
}
