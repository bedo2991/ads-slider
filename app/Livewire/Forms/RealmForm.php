<?php

namespace App\Livewire\Forms;

use App\Livewire\Traits\ErrorBanner;
use App\Models\Realm;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Form;

class RealmForm extends Form
{
    use ErrorBanner;

    public ?Realm $realm = null;

    #[Validate('required|max:50')]
    public $name = '';

    #[Validate('string|max:10|nullable')]
    public $locale = '';

    #[Validate('nullable|max:13')]
    public $nina_ars = '';

    #[Validate('nullable|numeric|between:-90,90')]
    public $lat = '';

    #[Validate('nullable|numeric|between:-180,180')]
    public $lon = '';

    #[Validate('nullable|max:32')]
    public $ow_api_key = '';

    #[Validate('nullable|max:20')]
    public $ow_city_id = '';

    #[Locked]
    public $orders_pull = '';

    #[Validate('nullable|active_url|max:1852')]
    public $orders_link = '';

    #[Validate('integer|min:0|max:86400')]
    public $orders_polling_frequency = 60; // in seconds

    public function saveRealm()
    {
        $validated = $this->validate();

        if ($this->nina_ars && strlen($this->nina_ars) > 7) {
            $this->nina_ars = substr($this->nina_ars, 0, -7).'0000000';
            $validated['nina_ars'] = $this->nina_ars;
        }

        if (! $this->realm) {
            $this->realm = new Realm;
        }

        $this->realm->fill($validated);
        $this->realm->save();
    }

    public function setRealm(?Realm $realm): void
    {
        if (! $realm || ! $realm->exists) {
            return;
        }
        $this->realm = $realm;
        $this->name = $realm->name;
        $this->nina_ars = $realm->nina_ars;
        $this->lat = $realm->lat;
        $this->lon = $realm->lon;
        $this->ow_api_key = $realm->ow_api_key;
        $this->ow_city_id = $realm->ow_city_id;
        $this->locale = $realm->locale;
        $this->orders_pull = $realm->orders_pull;
        $this->orders_link = $realm->orders_link;
        $this->orders_polling_frequency = $realm->orders_polling_frequency ?? 60; // default to 60 seconds
    }
}
