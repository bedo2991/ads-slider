<?php

namespace App\Livewire\Traits;

trait ErrorBanner
{
    public $errorMessage = '';

    public $level = 'danger';

    public $important = false;

    public function clearMessage()
    {
        $this->errorMessage = '';
    }

    public function setErrorMessage($message, $important = true)
    {
        $this->important = $important;
        $this->level = 'danger';
        $this->errorMessage = $message;
    }

    public function setWarningMessage($message, $important = false)
    {
        $this->important = $important;
        $this->level = 'warning';
        $this->errorMessage = $message;
    }

    public function setInfoMessage($message, $important = false)
    {
        $this->important = $important;
        $this->level = 'info';
        $this->errorMessage = $message;
    }

    public function setSuccessMessage($message, $important = false)
    {
        $this->important = $important;
        $this->level = 'success';
        $this->errorMessage = $message;
    }
}
