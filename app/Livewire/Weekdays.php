<?php

namespace App\Livewire;

use Livewire\Attributes\Modelable;
use Livewire\Component;

class Weekdays extends Component
{
    #[Modelable]
    public $value = '';

    public $name = '';

    public $label = '';

    public $slot = '';

    public $message = '';

    public $moSelected = false;

    public $tuSelected = false;

    public $weSelected = false;

    public $thSelected = false;

    public $frSelected = false;

    public $saSelected = false;

    public $suSelected = false;

    /**
     * This function gets call any time a checkbox toggles
     */
    public function updateString()
    {
        $this->checkboxesToString();
    }

    public function selectAll()
    {
        $this->value = '1234567';
        $this->stringToCheckboxes();
    }

    public function selectNone()
    {
        $this->value = '';
        $this->stringToCheckboxes();
    }

    private function checkboxesToString()
    {
        $res = $this->moSelected ? '1' : '';
        $res .= $this->tuSelected ? '2' : '';
        $res .= $this->weSelected ? '3' : '';
        $res .= $this->thSelected ? '4' : '';
        $res .= $this->frSelected ? '5' : '';
        $res .= $this->saSelected ? '6' : '';
        $res .= $this->suSelected ? '7' : '';
        $this->value = $res;
    }

    private function stringToCheckboxes()
    {
        $str = $this->value;
        $this->moSelected = strpos($str, '1') !== false;
        $this->tuSelected = strpos($str, '2') !== false;
        $this->weSelected = strpos($str, '3') !== false;
        $this->thSelected = strpos($str, '4') !== false;
        $this->frSelected = strpos($str, '5') !== false;
        $this->saSelected = strpos($str, '6') !== false;
        $this->suSelected = strpos($str, '7') !== false;
    }

    public function render()
    {
        if ($this->value) {
            $this->stringToCheckboxes();
        }

        return view('livewire.weekdays');
    }
}
