<?php

namespace App\Livewire;

use App\Events\SecurityAuditEvent;
use App\Livewire\Forms\MonitorForm;
use App\Livewire\Traits\TrimStringsAndConvertEmptyStringsToNull;
use App\Models\Monitor;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class EditMonitor extends Component
{
    use TrimStringsAndConvertEmptyStringsToNull;

    public MonitorForm $form;

    public function mount(?Monitor $monitor)
    {
        if ($monitor && $monitor->exists) {
            $this->authorize('update', $monitor);
        }

        $this->form->setMonitor($monitor);
        $realm = $this->form->monitor?->realm ?? auth()->user()->realm;
        if (empty($realm?->ow_api_key)) {
            $this->form->show_weather_forecast = false;
        }
    }

    public function updateMonitor()
    {
        $this->authorize('update', $this->form->monitor);
        $this->form->saveMonitor();

        event(new SecurityAuditEvent(
            action: 'monitor.updated',
            description: "Monitor '{$this->form->monitor->name}' (ID: {$this->form->monitor->id}) updated by user ID: ".auth()->id(),
            userId: auth()->id(),
            realmId: $this->form->monitor->realm_id,
            context: ['monitor_id' => $this->form->monitor->id]
        ));

        flash(__('The monitor has been updated'))->success();
        Log::channel('crud')->warning('Monitor updated', [
            'monitor' => $this->form->monitor,
            'user' => auth()->id(),
        ]);
        $this->redirectRoute('monitors.index');

    }

    public function createMonitor()
    {
        $this->authorize('create', Monitor::class);
        $this->form->saveMonitor();
        flash(__('The monitor has been created'))->success();
        Log::channel('crud')->warning('Monitor created', [
            'monitor' => $this->form->monitor,
            'user' => auth()->id(),
        ]);
        $this->redirectRoute('monitors.index');
    }

    public function render()
    {
        $realm = $this->form->monitor?->realm ?? auth()->user()->realm;
        $hasOpenweatherApiKey = ! empty($realm?->ow_api_key);

        return view('livewire.edit-monitor', compact('hasOpenweatherApiKey', 'realm'));
    }
}
