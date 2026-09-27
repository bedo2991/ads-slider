<?php

namespace Tests\Feature;

use App\Enums\ScheduledSlideType;
use App\Livewire\EditMonitor;
use App\Livewire\EditRealm;
use App\Models\Monitor;
use App\Models\Realm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ScheduleConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_realm_can_save_and_update_schedule(): void
    {
        $realm = Realm::factory()->create([
            'schedule' => null,
        ]);
        $user = User::factory()->create([
            'user_type' => 'admin',
            'realm_id' => $realm->id,
        ]);

        $inputText = "WEATHER\n  EVENTS  \n\nMENUS\nORDERSLIST\n";

        Livewire::actingAs($user)
            ->test(EditRealm::class, ['realm' => $realm])
            ->set('form.schedule', $inputText)
            ->call('updateRealm')
            ->assertHasNoErrors();

        $fresh = $realm->fresh();
        $this->assertEquals(['WEATHER', 'EVENTS', 'MENUS', 'ORDERSLIST'], $fresh->schedule);

        // Test clearing schedule
        Livewire::actingAs($user)
            ->test(EditRealm::class, ['realm' => $fresh])
            ->set('form.schedule', '')
            ->call('updateRealm')
            ->assertHasNoErrors();

        $this->assertNull($realm->fresh()->schedule);
    }

    public function test_realm_schedule_validation_rejects_invalid_slide_types(): void
    {
        $realm = Realm::factory()->create();
        $user = User::factory()->create([
            'user_type' => 'admin',
            'realm_id' => $realm->id,
        ]);

        Livewire::actingAs($user)
            ->test(EditRealm::class, ['realm' => $realm])
            ->set('form.schedule', "EVENTS\nUNKNOWN_SLIDE_TYPE\nWEATHER")
            ->call('updateRealm')
            ->assertHasErrors(['form.schedule']);
    }

    public function test_monitor_can_save_and_update_custom_schedule(): void
    {
        $realm = Realm::factory()->create([
            'schedule' => ['WEATHER', 'EVENTS'],
        ]);
        $user = User::factory()->create([
            'user_type' => 'admin',
            'realm_id' => $realm->id,
        ]);
        $monitor = Monitor::factory()->create([
            'realm_id' => $realm->id,
            'schedule' => null,
        ]);

        $customText = "PICS\nVIDEOS\nORDERSLIST";

        Livewire::actingAs($user)
            ->test(EditMonitor::class, ['monitor' => $monitor])
            ->set('form.schedule', $customText)
            ->call('updateMonitor')
            ->assertHasNoErrors();

        $fresh = $monitor->fresh();
        $this->assertEquals(['PICS', 'VIDEOS', 'ORDERSLIST'], $fresh->schedule);
        $this->assertEquals(['PICS', 'VIDEOS', 'ORDERSLIST'], $fresh->getEffectiveSchedule());

        // Test clearing custom schedule falls back to realm
        Livewire::actingAs($user)
            ->test(EditMonitor::class, ['monitor' => $fresh])
            ->set('form.schedule', '')
            ->call('updateMonitor')
            ->assertHasNoErrors();

        $this->assertNull($monitor->fresh()->schedule);
        $this->assertEquals(['WEATHER', 'EVENTS'], $monitor->fresh()->getEffectiveSchedule());
    }

    public function test_monitor_schedule_validation_rejects_invalid_slide_types(): void
    {
        $realm = Realm::factory()->create();
        $user = User::factory()->create([
            'user_type' => 'admin',
            'realm_id' => $realm->id,
        ]);
        $monitor = Monitor::factory()->create([
            'realm_id' => $realm->id,
            'schedule' => null,
        ]);

        Livewire::actingAs($user)
            ->test(EditMonitor::class, ['monitor' => $monitor])
            ->set('form.schedule', "EVENTS\nNON_EXISTENT_TYPE")
            ->call('updateMonitor')
            ->assertHasErrors(['form.schedule']);
    }

    public function test_effective_schedule_resolution_and_fallback(): void
    {
        $realmWithSchedule = Realm::factory()->create([
            'schedule' => ['WEATHER', 'MENUS'],
        ]);
        $realmWithoutSchedule = Realm::factory()->create([
            'schedule' => null,
        ]);

        // Monitor with custom schedule overrides realm
        $monitorOverride = Monitor::factory()->create([
            'realm_id' => $realmWithSchedule->id,
            'schedule' => ['PICS', 'VIDEOS'],
        ]);
        $this->assertEquals(['PICS', 'VIDEOS'], $monitorOverride->getEffectiveSchedule());

        // Monitor without schedule falls back to realm standard
        $monitorFallback = Monitor::factory()->create([
            'realm_id' => $realmWithSchedule->id,
            'schedule' => null,
        ]);
        $this->assertEquals(['WEATHER', 'MENUS'], $monitorFallback->getEffectiveSchedule());

        // Monitor and realm both without schedule falls back to system default schedule
        $monitorDefault = Monitor::factory()->create([
            'realm_id' => $realmWithoutSchedule->id,
            'schedule' => null,
        ]);
        $this->assertEquals(ScheduledSlideType::defaultSchedule(), $monitorDefault->getEffectiveSchedule());
    }

    public function test_collect_data_includes_effective_schedule_in_json(): void
    {
        $realm = Realm::factory()->create([
            'schedule' => ['WEATHER', 'EVENTS'],
        ]);
        $monitor = Monitor::factory()->create([
            'realm_id' => $realm->id,
            'schedule' => ['EVENTS', 'ORDERSLIST'],
        ]);

        $response = $this->get('Content/'.$monitor->api_token.'/data.json');
        $response->assertOk();
        $data = $response->json();

        $this->assertEquals(['EVENTS', 'ORDERSLIST'], $data['schedule']);
        $this->assertEquals(['EVENTS', 'ORDERSLIST'], $data['m']['schedule']);
    }

    public function test_display_endpoint_includes_schedule_in_view_data(): void
    {
        $realm = Realm::factory()->create([
            'schedule' => ['MENUS', 'EVENTS'],
        ]);
        $monitor = Monitor::factory()->create([
            'realm_id' => $realm->id,
            'schedule' => null,
        ]);

        $response = $this->get(route('showEventsToken', $monitor->api_token));
        $response->assertOk();
        $response->assertViewIs('showevents');

        $viewData = $response->viewData('data');
        $this->assertArrayHasKey('schedule', $viewData);
        $this->assertEquals(['MENUS', 'EVENTS'], $viewData['schedule']);
        $this->assertEquals(['MENUS', 'EVENTS'], $viewData['m']['schedule']);
    }

    public function test_extensible_slide_types_support(): void
    {
        $customTypeSchedule = ['CUSTOM_SLIDE', 'EVENTS'];
        $realm = Realm::factory()->create([
            'schedule' => $customTypeSchedule,
        ]);
        $monitor = Monitor::factory()->create([
            'realm_id' => $realm->id,
            'schedule' => null,
        ]);

        $this->assertEquals($customTypeSchedule, $realm->getEffectiveSchedule());
        $this->assertEquals($customTypeSchedule, $monitor->getEffectiveSchedule());
    }
}
