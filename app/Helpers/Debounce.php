<?php

namespace App\Helpers;

use App\Jobs\RunArtisanCommand;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class Debounce
{
    /**
     * Minimal shim for Zackaj\LaravelDebounce\Facades\Debounce::command
     * Supports named args: command, delay, parameters, uniqueKey, toQueue, outputBuffer
     */
    public static function command(string $command = '', int $delay = 0, array $parameters = [], ?string $uniqueKey = null, bool $toQueue = true, $outputBuffer = null)
    {
        if ($uniqueKey) {
            // try add key; if exists, skip execution
            $added = Cache::add($uniqueKey, true, max(60, $delay + 60));
            if (! $added) {
                return false;
            }
        }

        if ($toQueue) {
            RunArtisanCommand::dispatch($command, $parameters, $outputBuffer)->delay(now()->addSeconds($delay));

            return true;
        }

        // execute synchronously (no delay)
        Artisan::call($command, $parameters, $outputBuffer);

        return true;
    }
}
