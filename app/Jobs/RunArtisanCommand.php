<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;

class RunArtisanCommand implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $command;

    public array $parameters;

    public $outputBuffer;

    public function __construct(string $command, array $parameters = [], $outputBuffer = null)
    {
        $this->command = $command;
        $this->parameters = $parameters;
        $this->outputBuffer = $outputBuffer;
    }

    public function handle()
    {
        Artisan::call($this->command, $this->parameters, $this->outputBuffer);
    }
}
