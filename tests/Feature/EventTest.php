<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Monitor;
use App\Models\Template;
use App\Models\User;
// use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventTest extends TestCase
{
    // use DatabaseMigrations;
    // one or the other
    use RefreshDatabase;

    // GUESTS

    public function test_index_unlogged()
    {
        $response = $this->get('/events');
        $response->assertStatus(302);
    }

    public function test_show_unlogged()
    {
        $user = User::factory()->create();
        $event = Event::factory()->create(['user_id' => $user->id]);
        $response = $this->get('/events/'.$event->id);
        $response->assertStatus(404); // This page does not exist
    }

    public function test_events_post_unlogged()
    {
        $response = $this->post('/events', []);
        $response->assertStatus(405);
    }

    public function test_edit_unlogged()
    {
        $user = User::factory()->create();
        $event = Event::factory()->make();
        $event->user_id = $user->id;
        $event->save();
        $response = $this->get('/events/'.$event->id.'/edit');
        $response->assertStatus(302);
    }

    public function test_create_unlogged()
    {
        $response = $this->get('/events/create');
        $response->assertStatus(302);
    }

    public function test_create_with_template_unlogged()
    {
        $user = User::factory()->create();
        $template = Template::factory()->make();
        $template->user_id = $user->id;
        $user->templates()->save($template);
        $response = $this->get('/events/create/'.$template->id);
        $response->assertStatus(302);
    }

    public function test_monitor_with_wrong_api_key()
    {
        $response = $this->get('/showEvents/wrong_api_key');
        $response->assertStatus(403);
    }

    public function test_monitor_correct_api_key_unlogged()
    {
        $user = User::factory()->create();
        $monitor = Monitor::factory()->create(['user_id' => $user->id]);
        $response = $this->get('/showEvents/'.$monitor->api_token);
        $response->assertStatus(200);
    }

    public function test_monitor_no_api_key_unlogged()
    {
        $user = User::factory()->create();
        $monitor = Monitor::factory()->create(['user_id' => $user->id]);
        $response = $this->get('/showEvents/'.$monitor->id);
        $response->assertStatus(403);
    }

    public function test_monitor_no_api_key_logged()
    {
        $user = User::factory()->createOne();
        $monitor = Monitor::factory()->createOne(['user_id' => $user->id, 'realm_id' => $user->realm_id]);
        $response = $this->actingAs($user)->get('/monitors/'.$monitor->id);
        $response->assertStatus(302);
    }

    public function test_event_no_api_key_unlogged()
    {
        $user = User::factory()->create();
        $event = Event::factory()->create(['user_id' => $user->id]);
        $response = $this->get('/events/'.$event->id.'/edit');
        $response->assertRedirectToRoute('login');
    }

    public function test_event_wrong_api_key_unlogged()
    {
        $user = User::factory()->create();
        $event = Event::factory()->create(['user_id' => $user->id]);
        $response = $this->get('/events/'.$event->id.'/edit/WRONG_KEY');
        $response->assertStatus(403);
    }

    public function test_event_edit_no_api_key_logged()
    {
        $user = User::factory()->createOne(['user_type' => 'member']);
        $event = Event::factory()->create(['user_id' => $user->id, 'realm_id' => $user->realm_id]);
        $response = $this->actingAs($user)->get(route('events.edit', [$event->id]));
        $response->assertStatus(200);
    }
}
