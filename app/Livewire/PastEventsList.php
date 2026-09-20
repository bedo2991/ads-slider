<?php

namespace App\Livewire;

use App\Models\Event;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;

class PastEventsList extends EventsList
{
    #[Computed()]
    protected function allEvents()
    {
        return Event::ofRealm(auth()->user()->realm_id)->with('user')->whereHas('schedule', function ($q) {
            $q->where('end', '<', Carbon::today()->toDateString());
        });
    }

    #[Computed()]
    public function events()
    {
        return $this->allEvents->with('events_import')->where('name', 'LIKE', "%{$this->search}%")->paginate(10);
    }

    public function render(): View
    {
        return view(
            'livewire.events-list'
        )
            ->title(__('Past Events'))
            ->layout('livewire.master', ['header' => __('Past Events')]);
    }
}
