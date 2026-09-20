<?php

namespace App\Console\Commands;

use App\Models\Realm;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Support\Facades\Artisan;

class FetchAllWeatherForecasts extends Command implements Isolatable
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'weather:fetchAll';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pulls the weather data from the openweatherapi server and stores them to a json file, for all realms';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        Realm::all()->each(function ($realm) {
            $cityID = $realm->ow_city_id;
            $apiKey = $realm->ow_api_key;
            if (! $apiKey || ! $cityID) {
                $this->warn("Realm {$realm->name}: no api key ({$apiKey}) or city id({$cityID}) set.");

                return;
            }
            Artisan::call('weather:fetch', ['--realm' => $realm->id, '--city_id' => $cityID, '--api_key' => $apiKey]);
        });
    }
}
