<?php

namespace App\Http\Controllers;

use App\Events\SecurityAuditEvent;
use App\Models\Event;
use App\Models\Menu;
use App\Models\Monitor;
use App\Models\Picture;
use App\Models\Realm;
use App\Models\Schedule;
use App\Models\Video;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class MonitorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        Gate::authorize('viewAny', Monitor::class);
        $monitors = Monitor::withTrashed()->ofRealm(Auth::user()->realm_id)->orderBy('updated_at', 'DESC')->with('user')->paginate(10);

        return view('monitors.index', compact('monitors'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        Gate::authorize('create', Monitor::class);

        return view('monitors.create');
    }

    /**
     * Display the specified resource.
     */
    public function show(Monitor $monitor): RedirectResponse
    {
        Gate::authorize('view', $monitor);

        return redirect(route('showEventsToken', ['monitor' => $monitor->api_token]));
    }

    /**
     * If the request has a locale set, use it.
     * Otherwise, If the monitor has a locale set, use it.
     * Otherwise, use the preferred language from the Realm.
     */
    private function getLocale(Request $request, Monitor $monitor): string
    {
        $supportedLocales = ['de', 'en', 'it'];
        if ($request->has('locale')) {
            $loc = request()->get('locale');
            // Check if the locale is supported
            if (in_array($loc, $supportedLocales)) {
                return $loc;
            }
        }
        // if this monitor has a locale set, use it
        if ($monitor->locale) {
            $loc = $monitor->locale;
            // Check if the locale is supported
            if (in_array($loc, $supportedLocales)) {
                return $loc;
            }

            return $monitor->locale;
        }

        // otherwise, use the preferred language from the Realm
        if ($monitor->realm && $monitor->realm->locale) {
            $loc = $monitor->realm->locale;
            // Check if the locale is supported
            if (in_array($loc, $supportedLocales)) {
                return $loc;
            }
        }

        // default to the app locale
        return config('app.locale');
    }

    /**
     * @return Factory|View
     */
    public function display(Request $request, Monitor $monitor): View
    {
        $loc = $this->getLocale($request, $monitor);
        App::setLocale($loc);
        if (! $monitor->exists) {
            abort(404, 'Monitor not found or invalid token.');
        }
        Log::channel('connections')->info(
            'Monitor started',
            [
                'monitor' => $monitor->name,
                'locale' => $loc,
                'token' => $monitor->api_token,
                'ip' => $request->ip(),
            ]
        );

        if (! $monitor->realm->ow_city_id || ! $monitor->realm->ow_api_key) {
            $monitor->show_weather_forecast = false;
        }

        $data = $this->collectData($monitor);
        if ($monitor->show_weather_forecast) {
            try {
                $data['weather'] = $this->getWeather($monitor->realm->ow_city_id);
            } catch (FileNotFoundException $ex) {
                Log::channel('connections')->error('Weather data not found on the server');
            }
        }

        // Update the last_restarted_at and last_ping fields
        $now = now();
        $monitor->last_restarted_at = $now;
        $monitor->last_ping = $now;
        try {
            $monitor->saveQuietly();
        } catch (\Exception $e) {
            Log::channel('connections')->error('Error saving last_restarted_at', ['monitor' => $monitor->name, 'token' => $monitor->api_token]);
        }

        // Return the view with the initial data
        $data['locale'] = $loc;

        return view('showevents', [
            'data' => $data,
        ]);
    }

    public function getJson(Monitor $monitor): JsonResponse
    {
        $monitor->last_ping = now();
        try {
            $monitor->saveQuietly();
        } catch (\Exception $e) {
            Log::channel('connections')->error('Error saving last_restarted_at', ['monitor' => $monitor->name, 'token' => $monitor->api_token]);
        }

        return response()->json($this->collectData($monitor));
    }

    private function getScreenRatio($width, $height)
    {
        if ($width <= 0 || $height <= 0) {
            return '16:9';
        }

        $ratio = $width / $height;

        // Define the "perfect" decimal values for your target formats
        $targets = [
            '16:9' => 16 / 9,   // 1.777
            '16:10' => 16 / 10,  // 1.6
            '4:3' => 4 / 3,    // 1.333
            '21:9' => 21 / 9,   // 2.333
            '9:16' => 9 / 16,   // 0.562
            '1:1' => 1 / 1,    // 1.0
        ];

        $closestFormat = '16:9';
        $minDifference = PHP_FLOAT_MAX;

        foreach ($targets as $name => $value) {
            $diff = abs($ratio - $value);
            if ($diff < $minDifference) {
                $minDifference = $diff;
                $closestFormat = $name;
            }
        }

        return $closestFormat;
    }

    public function storeStats(Request $request, Monitor $monitor): void
    {
        $validated = $request->validate([
            'screen' => 'required',
            'navigation' => 'required|json',
            'timestamp' => 'required|date',
        ]);

        $validated['screen']['ratio'] = $this->getScreenRatio($validated['screen']['availWidth'], $validated['screen']['availHeight']);

        $monitor->stats = [
            'screen' => $validated['screen'],
            'navigation' => json_decode($validated['navigation']),
            'timestamp' => $validated['timestamp'],
        ];

        $monitor->saveQuietly();
    }

    /**
     * Returns an object containing the weather data read from the disk
     */
    private function getWeather(?string $city_id): mixed
    {
        if (! $city_id) {
            throw new FileNotFoundException('City ID not found');
        }
        if (Storage::disk('local')->exists("weather-{$city_id}.json")) {
            $fileContent = Storage::disk('local')->get("weather-{$city_id}.json");

            return json_decode($fileContent);
        }
        throw new FileNotFoundException('Weather data not found');
    }

    private function getOrdersList(Monitor $monitor): mixed
    {
        if ($monitor->show_orderslist === 0) {
            return null;
        }
        $lock = Cache::lock("lock-orderslist-$monitor->realm_id", 10);

        try {
            $lock->block(10);
            // Lock acquired after waiting a maximum of 10 seconds...
            $orderslistPath = $monitor->realm_id.'/orderslist-'.md5($monitor->realm->orders_link).'.json';
            if (Storage::disk('local')->exists($orderslistPath)) {
                if (Storage::disk('local')->lastModified($orderslistPath) < now()->subMinutes(5)->getTimestamp()) {
                    Log::channel('connections')->warning('Orders list is older than 5 minutes, not returning it', [
                        'monitor' => $monitor->name,
                    ]);

                    return null;
                }
                $fileContent = Storage::disk('local')->get($orderslistPath);

                return json_decode($fileContent, true);
            }
        } catch (LockTimeoutException $e) {
            // Unable to acquire lock...
            Log::error('Unable to acquire lock for orders list', [
                'monitor' => $monitor->name,
                'error' => $e->getMessage(),
            ]);
        } finally {
            $lock->release();
        }

        return null;
    }

    /**
     * Return an object with all the timestamps of the element updated last
     *
     * @deprecated
     */
    public function collectLastUpdate(Monitor $monitor)
    {
        $realmId = $monitor->realm_id;

        return [
            'version' => config('app.version'),
            'e' => Event::ofRealm($realmId)->latest('updated_at')->first()?->updated_at,
            'p' => Picture::ofRealm($realmId)->latest('updated_at')->first()?->updated_at,
            'ps' => Schedule::where('realm_id', $realmId)->where('scheduleable_type', 'PI')->latest('updated_at')->first()?->updated_at,
            'v' => Video::ofRealm($realmId)->latest('updated_at')->first()?->updated_at,
            'vs' => Schedule::where('realm_id', $realmId)->where('scheduleable_type', 'VI')->latest('updated_at')->first()?->updated_at,
            'mon' => Monitor::ofRealm($realmId)->latest('updated_at')->first()?->updated_at,
            'men' => Menu::ofRealm($realmId)->latest('updated_at')->first()?->updated_at,
        ];
    }

    /**
     * Return all data that can be displayed on this monitor
     */
    private function collectData(Monitor $monitor): array
    {
        $data = [];
        $data['version'] = config('app.version');
        // $data['latest'] = $this->collectLastUpdate($monitor);
        $data['m'] = $monitor->toArray();
        $data['m']['api_token'] = $monitor->api_token;
        $data['m']['channel_hash'] = Realm::getBroadcastChannelSecret($monitor->realm_id);
        $data['e'] = EventController::getScheduledEvents($monitor->realm_id)->with(['menus:id,path', 'happy_hour'])->get()->sortBy(function ($event) {
            return [
                $event->real_start_date,
                $event->start_time,
            ];
        })->values()->all();

        $pics = PictureSlideController::getScheduledPicturesOnMonitor($monitor);
        $data['p'] = $pics;

        $data['v'] = VideoSlideController::getScheduledVideosOnMonitor($monitor)?->get();
        $data['ol'] = $this->getOrdersList($monitor);
        $data['menus'] = [];

        $menus = [];
        foreach ($data['e'] as $key => $e) { // For each event
            if ($e->menus) {
                foreach ($e->menus as $mkey => $menu) { // For each menu
                    if ($menu->monitors()->exists()) { // If it has to be displayed on some monitors only
                        // Check if this menu should be shown on this monitor
                        $displayed = false;
                        foreach ($menu->monitors as $mon) { // For each monitor
                            if ($monitor->id === $mon->id) {
                                array_push($menus, $menu);
                                $displayed = true;
                                break;
                            }
                        }
                        // Remove the menu from the "menus" item of the event
                        if (! $displayed) {
                            $data['e'][$key]->menus->forget($mkey);
                        }
                    } else {
                        array_push($menus, $menu);
                    }
                }
                $idOnly = [];
                foreach ($data['e'][$key]->menus as $m) {
                    array_push($idOnly, $m->id);
                }
                $data['e'][$key]->menus = $idOnly;
            }
        }
        $menus = array_unique($menus);

        foreach ($menus as $m) {
            array_push($data['menus'], $m->json_content);
        }

        return $data;
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Monitor $monitor): View
    {
        $this->authorize('update', $monitor);

        return view('monitors.edit', compact('monitor'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @throws \Exception
     */
    public function destroy(Monitor $monitor): JsonResponse
    {
        Gate::authorize('delete', $monitor);
        // Detach all many to many relationships
        // abort(403, 'For safety reasons, only delete a monitor from the DB');
        DB::beginTransaction();
        $monitor->pictures()->detach();
        $monitor->menus()->detach();

        $monitor->delete();
        DB::commit();

        event(new SecurityAuditEvent(
            action: 'monitor.deleted',
            description: "Monitor '{$monitor->name}' (ID: {$monitor->id}) deleted by user ID: ".Auth::id(),
            userId: Auth::id(),
            realmId: $monitor->realm_id,
            context: ['monitor_id' => $monitor->id, 'monitor_name' => $monitor->name]
        ));

        flash(__('Monitor deleted'))->success();
        Log::channel('crud')->warning('Monitor deleted', [
            'monitor' => $monitor,
            'user' => Auth::id(),
        ]);

        return response()->json([
            'status' => 'success',
        ]);
    }
}
