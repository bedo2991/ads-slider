<?php

namespace Tests\Feature;

use App\Events\SecurityAuditEvent;
use App\Http\Controllers\MonitorController;
use App\Models\Event;
use App\Models\EventsImport;
use App\Models\Menu;
use App\Models\Monitor;
use App\Models\Picture;
use App\Models\Realm;
use App\Models\User;
use App\Models\Video;
use App\Providers\AlertCreated;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event as EventFacade;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CoreControllersTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $member;

    private Realm $realm;

    private Monitor $monitor;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');
        Http::fake([
            '*' => Http::response(['status' => 'ok'], 200),
        ]);

        $this->realm = Realm::factory()->create([
            'locale' => 'de',
            'orders_pull' => 'secret_pull_123',
            'orders_link' => 'https://orders.example.com/api',
            'ow_city_id' => '2867714',
            'ow_api_key' => 'fake_ow_api_key',
        ]);

        $this->admin = User::factory()->create([
            'user_type' => 'admin',
            'realm_id' => $this->realm->id,
        ]);

        $this->member = User::factory()->create([
            'user_type' => 'member',
            'realm_id' => $this->realm->id,
        ]);

        $this->monitor = Monitor::factory()->create([
            'user_id' => $this->member->id,
            'realm_id' => $this->realm->id,
            'locale' => null,
            'show_weather_forecast' => true,
            'show_pictures' => true,
            'show_videos' => true,
            'show_orderslist' => 0,
        ]);
    }

    // --- MONITOR CONTROLLER TESTS ---

    public function test_monitor_index_create_edit_views(): void
    {
        $response = $this->actingAs($this->member)->get(route('monitors.index'));
        $response->assertStatus(200);
        $response->assertViewIs('monitors.index');

        $response = $this->actingAs($this->member)->get(route('monitors.create'));
        $response->assertStatus(200);
        $response->assertViewIs('monitors.create');

        $response = $this->actingAs($this->member)->get(route('monitors.edit', $this->monitor->id));
        $response->assertStatus(200);
        $response->assertViewIs('monitors.edit');
    }

    public function test_monitor_show_redirects_to_show_events_token(): void
    {
        $response = $this->actingAs($this->member)->get(route('monitors.show', $this->monitor->id));
        $response->assertRedirect(route('showEventsToken', ['monitor' => $this->monitor->api_token]));
    }

    public function test_monitor_destroy_detaches_relations_and_dispatches_audit_event(): void
    {
        EventFacade::fake([SecurityAuditEvent::class]);

        $picture = Picture::factory()->create(['realm_id' => $this->realm->id]);
        $menu = Menu::factory()->create(['realm_id' => $this->realm->id]);

        $this->monitor->pictures()->attach($picture->id);
        $this->monitor->menus()->attach($menu->id);

        $controller = new MonitorController;
        $this->actingAs($this->admin);

        $this->expectException(AuthorizationException::class);
        $controller->destroy($this->monitor);

        EventFacade::assertDispatched(SecurityAuditEvent::class, function (SecurityAuditEvent $event) {
            return $event->action === 'monitor.deleted' && $event->context['monitor_id'] === $this->monitor->id;
        });
    }

    public function test_monitor_display_public_endpoint(): void
    {
        // Fake weather data in storage
        Storage::disk('local')->put("weather-{$this->realm->ow_city_id}.json", json_encode(['temp' => 22]));

        // 1. Monitor display with realm locale fallback ('de')
        $response = $this->get(route('showEventsToken', ['monitor' => $this->monitor->api_token]));
        $response->assertStatus(200);
        $response->assertViewIs('showevents');
        $response->assertViewHas('data.m.api_token', $this->monitor->api_token);
        $this->assertEquals('de', app()->getLocale());

        $this->monitor->refresh();
        $this->assertNotNull($this->monitor->last_restarted_at);
        $this->assertNotNull($this->monitor->last_ping);

        // 2. Monitor display with query param locale override ('it')
        $response = $this->get(route('showEventsToken', ['monitor' => $this->monitor->api_token, 'locale' => 'it']));
        $response->assertStatus(200);
        $this->assertEquals('it', app()->getLocale());

        // 3. Monitor with custom monitor locale ('en')
        $this->monitor->locale = 'en';
        $this->monitor->save();
        $response = $this->get(route('showEventsToken', ['monitor' => $this->monitor->api_token]));
        $response->assertStatus(200);
        $this->assertEquals('en', app()->getLocale());
    }

    public function test_monitor_get_json_feed(): void
    {
        // Add an event, a picture slide, and a video slide
        $event = Event::factory()->create(['realm_id' => $this->realm->id, 'user_id' => $this->member->id]);
        $picture = Picture::factory()->create(['realm_id' => $this->realm->id, 'user_id' => $this->member->id]);
        $video = Video::factory()->create(['realm_id' => $this->realm->id, 'user_id' => $this->member->id]);

        $response = $this->get('Content/'.$this->monitor->api_token.'/data.json');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'version',
            'm' => ['id', 'name', 'channel_hash'],
            'e',
            'p',
            'v',
            'ol',
            'menus',
        ]);
    }

    public function test_monitor_store_stats_calculates_aspect_ratios(): void
    {
        // 16:9 ratio test
        $response = $this->post('monitors/stats/'.$this->monitor->api_token, [
            'screen' => [
                'availWidth' => 1920,
                'availHeight' => 1080,
            ],
            'navigation' => json_encode(['userAgent' => 'Chrome']),
            'timestamp' => now()->toIso8601String(),
        ]);

        $response->assertStatus(200);

        $this->monitor->refresh();
        $this->assertNotNull($this->monitor->stats);
        $this->assertEquals('16:9', $this->monitor->stats['screen']['ratio']);

        // 4:3 ratio test
        $this->post('monitors/stats/'.$this->monitor->api_token, [
            'screen' => [
                'availWidth' => 1024,
                'availHeight' => 768,
            ],
            'navigation' => json_encode(['userAgent' => 'Chrome']),
            'timestamp' => now()->toIso8601String(),
        ]);

        $this->monitor->refresh();
        $this->assertEquals('4:3', $this->monitor->stats['screen']['ratio']);
    }

    public function test_monitor_collect_last_update(): void
    {
        $response = $this->get(route('latest', $this->monitor->api_token));
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'version',
            'e',
            'p',
            'ps',
            'v',
            'vs',
            'mon',
            'men',
        ]);
    }

    // --- USER CONTROLLER TESTS ---

    public function test_user_edit_view_shows_realms_for_admin(): void
    {
        $response = $this->actingAs($this->admin)->get(route('users.edit', $this->member->id));
        $response->assertStatus(200);
        $response->assertViewHas('realms');

        $response = $this->actingAs($this->member)->get(route('users.edit', $this->member->id));
        $response->assertStatus(200);
    }

    public function test_user_update_profile_and_realm_authorization(): void
    {
        EventFacade::fake([SecurityAuditEvent::class]);

        $newRealm = Realm::factory()->create();

        // 1. Normal member can update name & email, but cannot change realm_id
        $response = $this->actingAs($this->member)->put(route('users.update', $this->member->id), [
            'name' => 'Updated Member Name',
            'email' => 'updated_member@example.com',
            'realm_id' => $newRealm->id,
        ]);

        $response->assertRedirect(route('users.edit', $this->member->id));
        $this->member->refresh();
        $this->assertEquals('Updated Member Name', $this->member->name);
        $this->assertEquals('updated_member@example.com', $this->member->email);
        $this->assertEquals($this->realm->id, $this->member->realm_id); // Unchanged

        // 2. Admin can change member's realm_id
        $response = $this->actingAs($this->admin)->put(route('users.update', $this->member->id), [
            'name' => 'Updated By Admin',
            'email' => 'updated_member@example.com',
            'realm_id' => $newRealm->id,
        ]);

        $this->member->refresh();
        $this->assertEquals($newRealm->id, $this->member->realm_id);

        EventFacade::assertDispatched(SecurityAuditEvent::class);
    }

    // --- ALERT CONTROLLER TESTS ---

    public function test_alert_index_and_create_permissions(): void
    {
        EventFacade::fake([AlertCreated::class, SecurityAuditEvent::class]);

        // Non-admin blocked from alerts
        $response = $this->actingAs($this->member)->get(route('alerts.index'));
        $response->assertStatus(302); // redirected or 403

        $response = $this->actingAs($this->admin)->get(route('alerts.index'));
        $response->assertStatus(200);

        // Admin broadcasts alert
        $response = $this->actingAs($this->admin)->post('alerts', [
            'title' => 'Emergency Alert',
            'message' => 'Please clear the main floor immediately.',
            'link' => 'https://safety.example.com',
            'showFor' => 45,
            'level' => 2,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        EventFacade::assertDispatched(AlertCreated::class, function (AlertCreated $event) {
            return $event->data->title === 'Emergency Alert'
                && $event->data->message === 'Please clear the main floor immediately.'
                && $event->data->timeoutInSeconds === 45
                && $event->data->level === 2;
        });

        EventFacade::assertDispatched(SecurityAuditEvent::class, function (SecurityAuditEvent $event) {
            return $event->action === 'alert.broadcast_created';
        });
    }

    // --- EVENTS IMPORT CONTROLLER TESTS ---

    public function test_events_import_admin_actions(): void
    {
        $import = EventsImport::factory()->create([
            'user_id' => $this->admin->id,
            'realm_id' => $this->realm->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('eventsImports.index'));
        $response->assertStatus(200);

        $response = $this->actingAs($this->admin)->get(route('eventsImports.create'));
        $response->assertStatus(200);

        $response = $this->actingAs($this->admin)->get(route('eventsImports.edit', $import->id));
        $response->assertStatus(200);

        $response = $this->actingAs($this->admin)->post(route('eventsImports.run', $import->id));
        $response->assertStatus(200);

        $response = $this->actingAs($this->admin)->post(route('eventsImports.run', ['import' => $import->id, 'force' => true]));
        $response->assertStatus(200);
    }

    // --- REALM CONTROLLER TESTS ---

    public function test_realm_edit_and_force_update_orders_list(): void
    {
        EventFacade::fake([SecurityAuditEvent::class]);
        Http::fake([
            'https://orders.example.com/*' => Http::response(['orders' => []], 200),
        ]);

        $response = $this->actingAs($this->admin)->get(route('realms.edit', $this->realm->id));
        $response->assertStatus(200);

        // Public requestpull route with realm orders_pull token
        $response = $this->get(route('realm.requestpull', $this->realm->orders_pull));
        $response->assertStatus(204);

        EventFacade::assertDispatched(SecurityAuditEvent::class, function (SecurityAuditEvent $event) {
            return $event->action === 'realm.orders_list_fetch_forced'
                && $event->realmId === $this->realm->id;
        });
    }

    // --- MISC & SYSTEM ROUTES TESTS ---

    public function test_status_json_and_home_and_localization_routes(): void
    {
        // Status JSON
        $response = $this->get('status.json');
        $response->assertStatus(200);
        $response->assertJson(['status' => 'OK']);

        // Home
        $response = $this->actingAs($this->member)->get('/home');
        $response->assertStatus(200);

        // Language switcher
        $response = $this->get('lang/de');
        $response->assertSessionHas('locale', 'de');

        $response = $this->get('lang/it');
        $response->assertSessionHas('locale', 'it');
    }
}
