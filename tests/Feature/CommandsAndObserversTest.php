<?php

namespace Tests\Feature;

use App\Console\Commands\ImportEvents;
use App\Models\Event;
use App\Models\EventsImport;
use App\Models\HappyHour;
use App\Models\Menu;
use App\Models\Picture;
use App\Models\Realm;
use App\Models\Schedule;
use App\Models\User;
use App\Providers\AlertCreated;
use App\Providers\ItemCreated;
use App\Providers\ItemDeleted;
use App\Providers\ItemUpdated;
use App\Providers\OrderslistUpdated;
use App\Providers\WeatherDataUpdated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event as EventFacade;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CommandsAndObserversTest extends TestCase
{
    use RefreshDatabase;

    private Realm $realm;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');

        $this->realm = Realm::factory()->create([
            'ow_city_id' => '2867714',
            'ow_api_key' => 'valid_api_key',
            'orders_link' => 'https://orders.example.com/data.json',
            'lat' => 51.2,
            'lon' => 6.8,
        ]);

        $this->user = User::factory()->create([
            'realm_id' => $this->realm->id,
        ]);
    }

    // --- CONSOLE COMMANDS TESTS ---

    public function test_weather_fetch_command(): void
    {
        EventFacade::fake([WeatherDataUpdated::class]);

        Http::fake([
            'https://api.openweathermap.org/*' => Http::response([
                'city' => ['name' => 'Munich'],
                'list' => [['main' => ['temp' => 20]]],
            ], 200),
        ]);

        $exitCode = Artisan::call('weather:fetch', [
            '--realm' => $this->realm->id,
            '--city_id' => $this->realm->ow_city_id,
            '--api_key' => $this->realm->ow_api_key,
        ]);

        $this->assertEquals(0, $exitCode);
        Storage::disk('local')->assertExists("weather-{$this->realm->ow_city_id}.json");

        EventFacade::assertDispatched(WeatherDataUpdated::class, function (WeatherDataUpdated $event) {
            return ! empty($event->data);
        });
    }

    public function test_weather_fetch_all_command(): void
    {
        EventFacade::fake([WeatherDataUpdated::class]);

        Http::fake([
            'https://api.openweathermap.org/*' => Http::response(['weather' => 'sunny'], 200),
        ]);

        $exitCode = Artisan::call('weather:fetchAll');
        $this->assertEquals(0, $exitCode);
    }

    public function test_orderslist_fetch_command(): void
    {
        EventFacade::fake([OrderslistUpdated::class]);

        $ordersData = ['table1' => ['order' => 'Beer', 'status' => 'ready']];

        Http::fake([
            'https://orders.example.com/*' => Http::response($ordersData, 200),
        ]);

        $exitCode = Artisan::call('orderslist:fetch', [
            '--realm' => $this->realm->id,
            '--orders_link' => $this->realm->orders_link,
            '--important' => true,
        ]);

        $this->assertEquals(0, $exitCode);

        $path = $this->realm->id.'/orderslist-'.md5($this->realm->orders_link).'.json';
        Storage::disk('local')->assertExists($path);

        EventFacade::assertDispatched(OrderslistUpdated::class, function (OrderslistUpdated $event) {
            return $event->data['important'] === true;
        });
    }

    public function test_orderslist_fetch_all_command(): void
    {
        Http::fake([
            'https://orders.example.com/*' => Http::response(['orders' => []], 200),
        ]);

        $exitCode = Artisan::call('orderslist:fetch-all');
        $this->assertEquals(0, $exitCode);
    }

    public function test_nina_fetch_command(): void
    {
        EventFacade::fake([AlertCreated::class]);

        $dashboardResponse = [
            [
                'id' => 'alert_123',
                'onset' => now()->toIso8601String(),
                'expires' => now()->addHours(2)->toIso8601String(),
            ],
        ];

        $warningDetails = [
            'identifier' => 'alert_123',
            'msgType' => 'Alert',
            'info' => [
                [
                    'headline' => 'Heavy Storm Warning',
                    'description' => 'Heavy winds expected in the region.',
                    'severity' => 'Severe',
                    'language' => 'DE',
                ],
                [
                    'headline' => 'Heavy Storm Warning EN',
                    'description' => 'Heavy winds expected in the region EN.',
                    'language' => 'EN',
                ],
            ],
        ];

        Http::fake([
            'https://warnung.bund.de/api31/dashboard/*' => Http::response($dashboardResponse, 200),
            'https://warnung.bund.de/api31/warnings/alert_123.geojson' => Http::response([
                'type' => 'FeatureCollection',
                'features' => [
                    [
                        'type' => 'Feature',
                        'geometry' => [
                            'type' => 'Polygon',
                            'coordinates' => [
                                [[6.0, 51.0], [7.0, 51.0], [7.0, 52.0], [6.0, 52.0], [6.0, 51.0]],
                            ],
                        ],
                    ],
                ],
            ], 200),
            'https://warnung.bund.de/api31/warnings/alert_123.json' => Http::response($warningDetails, 200),
        ]);

        $exitCode = Artisan::call('nina:fetch', [
            '--realm' => $this->realm->id,
            '--ars' => '051110000000',
            '--lat' => $this->realm->lat,
            '--lon' => $this->realm->lon,
        ]);

        $this->assertEquals(0, $exitCode);

        EventFacade::assertDispatched(AlertCreated::class, function (AlertCreated $event) {
            return $event->data->title === 'Heavy Storm Warning'
                && $event->data->source === 'NINA'
                && $event->data->level === 3;
        });
    }

    public function test_nina_fetch_all_command(): void
    {
        Http::fake([
            'https://warnung.bund.de/*' => Http::response([], 200),
        ]);

        $exitCode = Artisan::call('nina:fetchAll');
        $this->assertEquals(0, $exitCode);
    }

    public function test_import_events_command_options_and_icon_guessing(): void
    {
        // 1. Test when no imports defined
        $exitCode = Artisan::call('import:events');
        $this->assertEquals(0, $exitCode);

        // 2. Test icon guessing static logic
        $this->assertContains(ImportEvents::guessIcon('Wine Tasting'), ['wine-bottle', 'wine-glass', 'wine-glass-empty']);
        $this->assertEquals('utensils', ImportEvents::guessIcon('Sunday Brunch'));
        $this->assertEquals('ghost', ImportEvents::guessIcon('Halloween Party'));
        $this->assertEquals('microphone-lines', ImportEvents::guessIcon('Friday Karaoke'));
        $this->assertEquals('dice', ImportEvents::guessIcon('Kniffel Tournament'));
        $this->assertNull(ImportEvents::guessIcon('Regular Unnamed Event 12345'));

        // 3. Test skipping disabled import
        $disabledImport = EventsImport::factory()->create([
            'realm_id' => $this->realm->id,
            'import_disabled' => true,
        ]);

        $exitCode = Artisan::call('import:events', [
            'source' => [$disabledImport->id],
        ]);
        $this->assertEquals(0, $exitCode);
    }

    // --- OBSERVERS TESTS ---

    public function test_event_observer_dispatches_events(): void
    {
        EventFacade::fake([ItemCreated::class, ItemUpdated::class, ItemDeleted::class]);

        $event = Event::factory()->create([
            'realm_id' => $this->realm->id,
            'user_id' => $this->user->id,
            'name' => 'Created Event',
        ]);

        EventFacade::assertDispatched(ItemCreated::class);

        $event->name = 'Updated Event Title';
        $event->save();

        EventFacade::assertDispatched(ItemUpdated::class);

        $event->delete();
        EventFacade::assertDispatched(ItemDeleted::class);
    }

    public function test_happy_hour_observer_dispatches_events(): void
    {
        EventFacade::fake([ItemCreated::class, ItemUpdated::class, ItemDeleted::class]);

        $event = Event::factory()->create([
            'realm_id' => $this->realm->id,
            'user_id' => $this->user->id,
        ]);

        $happyHour = HappyHour::factory()->create([
            'event_id' => $event->id,
            'drink' => 'Cocktails',
        ]);

        EventFacade::assertDispatched(ItemCreated::class);

        $happyHour->drink = 'Beers';
        $happyHour->save();

        EventFacade::assertDispatched(ItemUpdated::class);

        $happyHour->delete();
        EventFacade::assertDispatched(ItemDeleted::class);
    }

    public function test_menu_observer_dispatches_events(): void
    {
        EventFacade::fake([ItemCreated::class, ItemUpdated::class, ItemDeleted::class]);

        $menu = Menu::factory()->create([
            'realm_id' => $this->realm->id,
            'user_id' => $this->user->id,
            'name' => 'Summer Menu',
        ]);

        EventFacade::assertDispatched(ItemCreated::class);

        $menu->name = 'Autumn Menu';
        $menu->save();

        EventFacade::assertDispatched(ItemUpdated::class);

        $menu->delete();
        EventFacade::assertDispatched(ItemDeleted::class);
    }

    public function test_schedule_observer_dispatches_events(): void
    {
        EventFacade::fake([ItemCreated::class, ItemUpdated::class, ItemDeleted::class]);

        $picture = Picture::factory()->create(['realm_id' => $this->realm->id]);

        $schedule = Schedule::factory()->create([
            'scheduleable_type' => 'PI',
            'scheduleable_id' => $picture->id,
            'realm_id' => $this->realm->id,
            'user_id' => $this->user->id,
            'disabled' => false,
        ]);

        EventFacade::assertDispatched(ItemCreated::class);

        $schedule->disabled = true;
        $schedule->save();

        EventFacade::assertDispatched(ItemUpdated::class);

        $schedule->delete();
        EventFacade::assertDispatched(ItemDeleted::class);
    }
}
