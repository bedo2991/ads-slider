<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\EventsImport;
use App\Models\Schedule;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ImportEvents extends Command implements Isolatable
{
    public const WARNING_TAG = '[WARNING]';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:events 
    {source?* : optional list of imports IDs to pull from}
    {--D|even-if-disabled : Also pull events from disabled sources}
    {--F|force : Overwrite all received events with the new ones even if they were edited locally, unless locked.}';

    // example: php artisan import:events 1 2 3 -D --force

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import events from third sources';

    /*
    private $defaultSectionValues = array(
        "icon" => "music",
        "color" => "#AAFF00",
        "final_round_confirmed" => true,
        "preparation_time" => 35,
        "not_closing" => false,
        "place" => "BD-Clubport",
        "is_karaoke" => 0,
        "disabled" => 0,
        "Computer" => 98
    );*/

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->newLine();
        $this->info('Importing events…');
        $this->info('Started: '.now());

        $even_if_disabled = $this->option('even-if-disabled') == true ? true : false;
        $forced = $this->option('force') == true ? true : false;
        $this->info('Forced: '.($forced ? 'true' : 'false'));

        $sources = $this->argument('source');

        $noErrors = true;

        if (count($sources) == 0) {
            $this->info('Analyzing all imports…');
            $imports = EventsImport::all();
        } else {
            $this->info('Importing only events from import with ID: '.implode(',', $sources));
            $imports = EventsImport::where(['id' => $sources])->get();
        }

        if ($imports->count() == 0) {
            $this->warn('No imports have been defined or none was found with the given IDs.');
            Log::channel('events_imports')->warning('No imports found (with the given IDs)');
        } else {
            foreach ($imports as $in) {
                if ($in->import_disabled && ! $even_if_disabled) {
                    $this->info("Skipping '{$in->import_name}': disabled.");

                    continue;
                }
                $this->info("Importing events from '{$in->import_name}'");
                Log::channel('events_imports')->info("Importing events from '{$in->import_name}'");
                $url = $in->import_url;
                $res = $this->import($in);

                $this->newLine();
                if ($res) {
                    $this->info("Events imported from '{$url}' successfully");
                    Log::channel('events_imports')->info("Events imported from '{$url}' successfully");
                } else {
                    $this->error("Failed to import events from '{$url}'");
                    Log::channel('events_imports')->error("Failed to import events from '{$url}'");
                    $noErrors = false;
                }
            }
        }

        $this->newLine();
        $this->info('Finished: '.now());
        if ($noErrors) {
            $this->info('Import: Success!');
            $this->info('======================');

            return Command::SUCCESS;
        }

        $this->error('Import: FAIL');
        $this->info('======================');

        return Command::FAILURE;
    }

    /**
     * Import the events from a given URL
     *
     * @return bool success
     */
    private function import(EventsImport $in): bool
    {
        $validationErrorFound = false;

        $url = $in->import_url;
        $client = new Client([
            'verify' => config('app.env', 'production') == 'production' ? true : false,
            'base_uri' => $url,
        ]);
        $response = null;
        try {
            $response = $client->get('');
        } catch (Exception $e) {
            $this->error("Guzzle error while contacting {$url}");
            $this->error($e->getMessage());
            Log::channel('events_imports')->error('Guzzle error: '.$e->getMessage());

            return false;
        }
        if ($response->getStatusCode() != 200) {
            $this->error("Contacting {$url} received the response code {$response->getStatusCode()}, expected HTTP 200");
            Log::channel('events_imports')->error("Contacting {$url} received the response code {$response->getStatusCode()}, expected HTTP 200");

            return false;
        }
        $data = $response->getBody()->getContents();
        $json = \json_decode($data, false);
        if (is_null($json) || ! is_array($json->events)) {
            $this->error("Something went wrong while parsing the events from the URL {$url}");
            Log::channel('events_imports')->error("Something went wrong while parsing the events from the URL {$url}");

            return false;
        }
        $receivedEventsIDs = [];

        foreach ($json->events as $i => $event) {
            $this->newLine();
            $this->info("Received event #{$i} '{$event->name}' ({$event->import_id})");
            $receivedEventsIDs[] = $event->import_id;

            $updatedOn = Carbon::parse($event->updated_on)->setTimezone(config('app.timezone'));

            // Check if this event was already imported
            $checkEventExists = Event::where([
                'import_id' => $event->import_id,
                'realm_id' => $in->realm_id,
            ])->first(['id', 'updated_at']);
            if ($checkEventExists) {
                $this->info('Event was already imported before in realm '.$in->realm_id);
                if (! $this->option('force') && $updatedOn->lte($checkEventExists->updated_at)) {
                    $this->info("Skipped: our updated_at timestamp {$checkEventExists->updated_at} is equal or more recent than {$updatedOn}.");

                    continue;
                } else {
                    $this->info("Event was edited remotely on {$updatedOn}. Our record was last updated on {$checkEventExists->updated_at}");
                    if ($checkEventExists->is_protected) {
                        $this->info('Event is locked, skipping');

                        continue;
                    }
                    $this->info('Event is not locked, importing it again…');
                    // TODO: here we should check what was changed and update our entry instead of deleting it
                    $checkEventExists->delete();
                }
            } else {
                $this->info('Not imported yet');
            }

            $importedEvent = get_object_vars($event);

            // Validate it
            $validator = Validator::make($importedEvent, [
                'import_id' => 'required|string|max:36',
                'name' => 'required|string|max:191',
                'start' => 'required|date_format:Y-m-d',
                'start_time' => 'required|date_format:H:i:s',
                'end' => 'required|date_format:Y-m-d',
                'end_time' => 'required|date_format:H:i:s',
                'updated_on' => 'required|date',
                'cancelled' => 'nullable|boolean',
                'place' => 'nullable',
                'link' => 'nullable|url:https',
            ]);

            if ($validator->fails()) {
                $validationErrorFound = true;
                $this->error("ERROR: Remote event '{$event->name}' ({$event->import_id}) did not pass the validation. Skipping.");
                $this->error(collect($validator->errors()->all(), ' - '));
                Log::channel('events_imports')->error("Remote event '{$event->name}' ({$event->import_id}) did not pass the validation");

                continue;
            }

            // Add it to the database
            $e = new Event;
            // Set the default values from the import
            $e->fill($in->attributesToArray());

            $validatedData = $validator->validated();
            // Internally, the event goes until the full minute ends. So if the end_time is 20:00, it means the event is running until 19:59.
            // The .59 is automatically added in a second time.
            $validatedData['end_time'] = Carbon::parse($validatedData['end_time'])->subMinute()->format('H:i');
            $scheduleData = [
                'start' => $validatedData['start'],
                'end' => $validatedData['end'],
                'start_time' => $validatedData['start_time'],
                'end_time' => $validatedData['end_time'],
                'disabled' => false, // Default for new events
            ];

            // Remove schedule fields from event data
            unset($validatedData['start'], $validatedData['end'], $validatedData['start_time'], $validatedData['end_time']);

            // set the actual values from the event
            $e->fill($validatedData);

            // Replace newlines with "\n" - maybe this should be done in the model?
            $e->name = str_replace(['\\n', "\n"], ["\n", '\\n'], $e->name);
            $e->place = str_replace(['\\n', "\n"], ["\n", '\\n'], $e->place);

            if (! property_exists($event, 'icon')) {
                $e->icon = ImportEvents::guessIcon($event->name) ?? $in->icon;
                $this->info("Icon for $e->name: $e->icon");
            }

            $e->realm_id = $in->realm_id;
            $e->created_at = now();
            $e->updated_at = $updatedOn;
            $e->api_token = Str::random(80);
            $e->events_import_id = $in->id;

            if ($e->save()) {
                // Create Schedule
                $schedule = new Schedule($scheduleData);
                $schedule->scheduleable_id = $e->id;
                $schedule->scheduleable_type = 'EV';
                $schedule->realm_id = $e->realm_id;
                $schedule->user_id = $e->user_id;
                $schedule->save();

                $this->info('Added to the DB');
            } else {
                $this->error('Could not store event');
            }
        } // foreach end

        // Delete future imported events that were not present in the current import
        $toBeDeleted = Event::where('events_import_id', $in->id)
            ->whereNotIn('import_id', $receivedEventsIDs)
            ->whereHas('schedule', function ($q) {
                $q->where('start', '>=', Carbon::today(config('app.timezone')));
            })->get();

        $this->newLine();
        $this->info('Checking if anything should be deleted…');
        if ($toBeDeleted->count() > 0) {
            foreach ($toBeDeleted as $deleteEvent) {
                $this->info("Deleting future event '{$deleteEvent->name}' ({$deleteEvent->import_id}) as it is not available anymore on the remote server");
                $deleteEvent->delete();
            }
        } else {
            $this->info('No events to delete');
        }

        if ($validationErrorFound) {
            // WARNING_TAG is used by other methods to check if there were any validation errors
            $this->warn(ImportEvents::WARNING_TAG.' one or more event validation errors were found during this run!');
        }

        return true;
    }

    /**
     * Guesses an icon based on the event name
     *
     * @param{$eventName}
     */
    public static function guessIcon(string $eventName): ?string
    {
        $array = [
            // https://docs.google.com/spreadsheets/d/1Ig1DAFg4pOM0hu_XoEMYV6je3YACZiHAB-y6SNj7k9M/edit
            (object) ['regex' => "/\b(Wein|Wine)/i", 'icons' => ['wine-bottle', 'wine-glass', 'wine-glass-empty']],
            (object) ['regex' => "/\b2000er/i", 'icons' => ['2']],
            (object) ['regex' => "/\b90(s|er)/i", 'icons' => ['9']],
            (object) ['regex' => "/\b80(s|er)/i", 'icons' => ['8']],
            (object) ['regex' => "/Brunch\b/i", 'icons' => ['utensils']],
            (object) ['regex' => "/\bCampus Noir/i", 'icons' => ['moon']],
            (object) ['regex' => "/\bTurnier/i", 'icons' => ['medal', 'trophy', 'award']],
            (object) ['regex' => "/\bEle[ck]tro/i", 'icons' => ['bolt', 'lightbulb']],
            (object) ['regex' => "/\bAfrica/i", 'icons' => ['earth-africa']],
            (object) ['regex' => "/\bLatin/i", 'icons' => ['earth-americas']],
            (object) ['regex' => "/\bJam\s?session/i", 'icons' => ['guitar', 'drum']],
            (object) ['regex' => "/\bWalpurginacht/i", 'icons' => ['star']],
            (object) ['regex' => "/\bTech(no)?/i", 'icons' => ['headphones']],
            (object) ['regex' => "/\bCocktail/i", 'icons' => ['martini-glass-citrus']],
            (object) ['regex' => "/\bB[ie]er\s?pong/i", 'icons' => ['table-tennis-paddle-ball']],
            (object) ['regex' => "/\bLiC:?\b/i", 'icons' => ['star']],
            (object) ['regex' => "/\bKunst/i", 'icons' => ['palette']],
            (object) ['regex' => "/\bHFC\b/i", 'icons' => ['film']],
            (object) ['regex' => "/\bFrühstück\b/i", 'icons' => ['utensils']],
            (object) ['regex' => '/rotation/i', 'icons' => ['rotate']],
            (object) ['regex' => '/Vortrag/i', 'icons' => ['person-chalkboard']],
            (object) ['regex' => "/Fluten\b/i", 'icons' => ['shower']],
            (object) ['regex' => "/\bWeihnachts/i", 'icons' => ['sleigh']],
            (object) ['regex' => "/\b(Beer|Bier)/i", 'icons' => ['beer-mug-empty']],
            (object) ['regex' => "/\bBeach/i", 'icons' => ['umbrella-beach']],
            (object) ['regex' => "/DJ[\s-]Café/i", 'icons' => ['umbrella-beach']],
            (object) ['regex' => "/Karaoke\b/i", 'icons' => ['microphone-lines']],
            (object) ['regex' => "/küche\b/i", 'icons' => ['utensils']],
            (object) ['regex' => '/Kniffel/i', 'icons' => ['dice']],
            (object) ['regex' => '/costume/i', 'icons' => ['mask']],
            (object) ['regex' => '/neujahr/i', 'icons' => ['calendar-plus']],
            (object) ['regex' => '/Cantina/i', 'icons' => ['bottle-droplet']],
            (object) ['regex' => '/halloween/i', 'icons' => ['ghost']],
            (object) ['regex' => '/helloween/i', 'icons' => ['ghost']],
            (object) ['regex' => "/\bHouse\b/i", 'icons' => ['house']],
            (object) ['regex' => "/\bIndian?/i", 'icons' => ['earth-asia']],
            (object) ['regex' => "/\bKlugscheißen\b/i", 'icons' => ['brain']],
            (object) ['regex' => "/\b(Grill(en)?|BBQ)\b/i", 'icons' => ['fire-extinguisher']],
        ];

        foreach ($array as $el) {
            if (preg_match($el->regex, $eventName)) {
                return $el->icons[array_rand($el->icons)];
            }
        }

        return null;
    }
}
