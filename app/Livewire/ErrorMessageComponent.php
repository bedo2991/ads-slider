<?php

namespace App\Livewire;

use Livewire\Component;

/**
 * Flash messages can only be used before a redirect. In a livewire component or form, add the banner component using
 *     <x-forms.helpers.banner :bannerMessage="$errorMessage" :level="$level"/>
 */
class ErrorMessageComponent extends Component
{
    public $errorMessage = '';

    public $level = 'danger';

    public function clearMessage()
    {
        $this->errorMessage = '';
    }

    public function setErrorMessage($message)
    {
        $this->level = 'danger';
        $this->errorMessage = $message;
    }

    public function setWarningMessage($message)
    {
        $this->level = 'warning';
        $this->errorMessage = $message;
    }

    public function setInfoMessage($message)
    {
        $this->level = 'info';
        $this->errorMessage = $message;
    }

    public function setSuccessMessage($message)
    {
        $this->level = 'success';
        $this->errorMessage = $message;
    }
}
