<?php

namespace App\Livewire;

use App\Models\Menu;
use App\Models\Monitor;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

class CreateMenu extends Component
{
    use WithFileUploads;

    #[Validate('required|extensions:json|mimes:json')]
    public $upload;

    #[Validate('required|max:191')]
    public $name = '';

    #[Validate(['monitors' => 'nullable|array', 'monitors.*' => 'integer|exists:monitors,id'])]
    public $monitors;

    public function save()
    {
        $this->authorize('create', Menu::class);
        $this->validate();

        $menu = new Menu;
        $menu->realm_id = Auth::user()->realm_id;
        $menu->user_id = Auth::id();
        $menu->name = $this->name;

        // Make a image name based on user name and current timestamp
        $name = Str::slug($this->upload->getClientOriginalName()).'_'.rand(0, 32000000).'.json';
        // Define folder path
        $folder = config('ads.menu_basepath');
        // Set the picture path in the model
        $menu->path = $name;

        $this->upload->storePubliclyAs($folder, $name, 'public');

        $menu->save();
        if ($this->monitors) {
            $validMonitorIds = Monitor::ofRealm(Auth::user()->realm_id)
                ->whereIn('id', (array) $this->monitors)
                ->pluck('id');
            $menu->monitors()->attach($validMonitorIds);
        }
        Log::channel('crud')->info('Menu created', [
            'menu' => $menu,
            'user' => Auth::id(),
        ]);
        flash()->success('Menu created');
        $this->reset();

        return $this->redirectRoute('menus.index');
    }

    public function render()
    {
        return view('livewire.create-menu');
    }
}
