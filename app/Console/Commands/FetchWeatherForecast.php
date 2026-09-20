<?php

namespace App\Console\Commands;

use App\Providers\WeatherDataUpdated;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class FetchWeatherForecast extends Command implements Isolatable
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'weather:fetch
    {--C|city_id= : the city ID, get it from https://openweathermap.org/ in the URL after looking for the required city}
    {--K|api_key= : your OpenWeatherAPI key}
    {--R|realm= : the realm ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pulls the weather data from the openweatherapi server and stores them to a json file';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $realmID = $this->option('realm');
        if (! $realmID) {
            $this->error('No realm ID provided');

            return;
        }

        $cityID = $this->option('city_id');
        $apiKey = $this->option('api_key');

        if (! $apiKey || ! $cityID) {
            $this->error('No api key provided.');

            return;
        }

        if (! $cityID) {
            $this->error('No city ID provided.');

            return;
        }

        $url = "https://api.openweathermap.org/data/2.5/forecast?id={$cityID}&lang=".config('app.locale', 'en')."&units=metric&cnt=6&appid={$apiKey}";
        $this->info("Pulling weather data from {$url} …");
        $response = Http::withoutVerifying()->retry(3, 10000)->get($url);
        $response->throw();
        if ($response->successful()) {
            if (! Storage::disk('local')->put("weather-{$cityID}.json", $response->body())) {
                $this->error('Could not write weather data to '.Storage::disk('local')->path("weather-{$cityID}.json"));
            } else {
                event(new WeatherDataUpdated($response->json(), $realmID));
            }
        }
    }
}
