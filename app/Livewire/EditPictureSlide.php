<?php

namespace App\Livewire;

use App\Events\SecurityAuditEvent;
use App\Livewire\Forms\PictureSlideForm;
use App\Livewire\Traits\TrimStringsAndConvertEmptyStringsToNull;
use App\Models\Schedule;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class EditPictureSlide extends Component
{
    use TrimStringsAndConvertEmptyStringsToNull;

    public PictureSlideForm $form;

    public $allPictures = [];

    public $action = 'create';

    public function createPictureSlide()
    {
        $this->authorize('create', Schedule::class);
        $this->form->store();
        flash(__('The picture slide has been created'))->success();
        Log::channel('crud')->info('PictureSlide created', [
            'pictureSlide' => $this->form->schedule, // logged as schedule
            'user' => auth()->id(),
        ]);
        $this->redirectRoute('picSlides.index');
    }

    public function updatePictureSlide()
    {
        // $this->authorize('update', $this->form->schedule);
        // Using PictureSlide class for policy if SchedulePolicy doesn't exist?
        // Or assume $this->form->schedule is sufficient if policy handles Schedule.
        // Keeping PictureSlide for now as it's likely mapped.
        // Actually, if we use Schedule object, we should probably pass it.
        $this->authorize('update', $this->form->schedule);

        $this->form->store();
        flash(__('The picture slide has been updated'))->success();
        Log::channel('crud')->info('PictureSlide updated', [
            'pictureSlide' => $this->form->schedule,
            'user' => auth()->id(),
        ]);
        $this->redirectRoute('picSlides.index');
    }

    public function deletePictureSlide()
    {
        $this->authorize('delete', $this->form->schedule);
        $schedule = $this->form->schedule;
        $this->form->delete();
        event(new SecurityAuditEvent(
            action: 'schedule.deleted',
            description: "Picture slide schedule (ID: {$schedule->id}) deleted for Picture ID: {$schedule->scheduleable_id}",
            userId: auth()->id(),
            realmId: $schedule->realm_id,
            context: [
                'schedule_id' => $schedule->id,
                'picture_id' => $schedule->scheduleable_id,
                'type' => 'PI',
            ]
        ));
        $this->skipRender();
        $this->redirectRoute('picSlides.index');
    }

    public function mount($action, $allPictures, ?Schedule $picSlide = null, $selected_picture = null)
    {
        if ($picSlide && $picSlide->exists) {
            $this->authorize('update', $picSlide);
        } else {
            $this->authorize('create', Schedule::class);
        }
        $this->action = $action;
        $this->form->setPictureId($selected_picture);
        $this->allPictures = $allPictures;
        // Assume picSlide is a Schedule
        if ($picSlide && $picSlide->exists) {
            $this->form->setSchedule($picSlide);
        }
    }

    public function render()
    {
        return view('livewire.edit-picture-slide');
    }
}
