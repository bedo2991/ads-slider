<?php

namespace App\Console\Commands;

use App\Models\Realm;
use App\Providers\AlertCreated;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class FetchNina extends Command implements Isolatable
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nina:fetch
    {--A|ars= : the Kreis ID (the last 7 digits of the city must be 0s}
    {--R|realm= : the realm ID}
    {--lat= : Latitude for geo-fencing}
    {--lon= : Longitude for geo-fencing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pulls the warning data from the the nina api';

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
        $ars = $this->option('ars');
        if (! $ars) {
            $this->error('No ARS provided');

            return;
        }

        $lat = $this->option('lat');
        $lon = $this->option('lon');

        if (! $lat || ! $lon) {
            $realm = Realm::find($realmID);
            if ($realm) {
                $lat = $lat ?: $realm->lat;
                $lon = $lon ?: $realm->lon;
            }
        }

        $url = "https://warnung.bund.de/api31/dashboard/{$ars}.json";
        $this->info("Pulling nina dashboard data from {$url} …");
        $response = Http::withoutVerifying()
            ->retry(3, 10000)
            ->withHeaders(
                ['accept' => 'application/json']
            )->get($url);
        $response->throw();
        if ($response->successful()) {
            $res = json_decode($response->body());
            if (! is_array($res)) {
                $this->error('Request did not return an array');

                return;
            }
            if (empty($res)) {
                $this->info('No warnings');

                return;
            }
            foreach ($res as $r) {
                if ($lat && $lon) {
                    if (! $this->isLocationInWarningArea($r->id, (float) $lat, (float) $lon)) {
                        $this->info("Alert {$r->id} is outside the defined area.");

                        continue;
                    }
                }

                $alert = $this->fetchEventDetails($r->id);
                if (! is_null($alert)) {

                    $alert_level = 1;
                    if ($alert->type === 'Cancel') {
                        $alert_level = 0;
                    } elseif ($alert->severity === 'Severe') {
                        $alert_level = 3;
                    }
                    $data = (object) [
                        'title' => $alert->title,
                        'message' => $alert->description,
                        'title_en' => $alert->title_en,
                        'message_en' => $alert->description_en,
                        'url' => "https://warnung.bund.de/meldungen/{$alert->id}",
                        'timeoutInSeconds' => 60,
                        'level' => $alert_level,
                        'start' => $r->onset ?? null,
                        'end' => $r->expires ?? null,
                        'realm_id' => $realmID,
                        'source' => 'NINA',
                    ];
                    event(new AlertCreated($data));
                }
            }
            // store it, so far just for debugging
            if (! Storage::disk('local')->put("nina-{$ars}.json", $response->body())) {
                $this->error('Could not write NINA data to '.Storage::disk('local')->path("nina-{$ars}.json"));
            }
        }
    }

    private function fetchEventDetails(string $id): object
    {
        $url = "https://warnung.bund.de/api31/warnings/{$id}.json";
        $this->info("Pulling warning data from {$url} …");
        $response = Http::withoutVerifying()
            ->retry(3, 10000)
            ->withHeaders(
                ['accept' => 'application/json']
            )->get($url);
        $response->throw();
        if ($response->successful()) {
            $res = json_decode($response->body());
            if (! is_object($res)) {
                $this->error('Request did not return an object');

                return null;
            }

            // Look for English info block
            $title_en = null;
            $description_en = null;
            if (isset($res->info) && is_array($res->info)) {
                foreach ($res->info as $info) {
                    if (isset($info->language) && strtoupper($info->language) === 'EN') {
                        $title_en = $info->headline ?? null;
                        $description_en = $info->description ?? null;
                        break;
                    }
                }
            }

            return (object) [
                'title' => $res->info[0]?->headline,
                'title_en' => $title_en,
                'description_en' => $description_en,
                'description' => $res->info[0]?->description,
                'severity' => $res->info[0]?->severity, // 'Minor', 'Severe'
                'type' => $res->msgType, // 'Cancel'
                'id' => $res->identifier,
            ];
        }

        return null;
    }

    private function isLocationInWarningArea(string $id, float $lat, float $lon): bool
    {
        $url = "https://warnung.bund.de/api31/warnings/{$id}.geojson";
        $this->info("Pulling event geometry from {$url} …");
        $response = Http::withoutVerifying()->get($url);

        if (! $response->successful()) {
            $this->error("Could not fetch geojson for $id");

            return true;
        }

        $geojson = $response->json();
        if (! isset($geojson['features'])) {
            return true;
        }

        foreach ($geojson['features'] as $feature) {
            if ($this->isPointInFeature($lat, $lon, $feature)) {
                return true;
            }
        }

        return false;
    }

    private function isPointInFeature($lat, $lon, $feature)
    {
        $type = $feature['geometry']['type'] ?? null;
        $coordinates = $feature['geometry']['coordinates'] ?? [];

        if ($type === 'Polygon') {
            // coordinates[0] is the outer ring
            return $this->pointInPolygon($lon, $lat, $coordinates[0]);
        } elseif ($type === 'MultiPolygon') {
            foreach ($coordinates as $polygon) {
                if ($this->pointInPolygon($lon, $lat, $polygon[0])) {
                    return true;
                }
            }
        }

        return false;
    }

    private function pointInPolygon($lon, $lat, $polygon)
    {
        $c = false;
        $l = count($polygon);
        $j = $l - 1;
        for ($i = 0; $i < $l; $j = $i++) {
            $xi = $polygon[$i][0];
            $yi = $polygon[$i][1];
            $xj = $polygon[$j][0];
            $yj = $polygon[$j][1];

            $intersect = (($yi > $lat) != ($yj > $lat))
                && ($lon < ($xj - $xi) * ($lat - $yi) / ($yj - $yi) + $xi);
            if ($intersect) {
                $c = ! $c;
            }
        }

        return $c;
    }
}
