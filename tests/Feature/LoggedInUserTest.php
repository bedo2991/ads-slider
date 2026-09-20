<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventsImport;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class LoggedInUserTest extends TestCase
{
    // use DatabaseMigrations;
    // one or the other
    use RefreshDatabase;

    private $user = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['user_type' => 'member']);
        $this->actingAs($this->user);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_user_is_logged_in(): void
    {
        $this->assertTrue(Auth::check());
    }

    public function test_user_can_list_events(): void
    {
        $response = $this->get(route('events.index'));
        $response->assertStatus(200);
    }

    public function test_user_can_create_event(): void
    {
        $response = $this->get(route('events.create'));
        $response->assertStatus(200);
    }

    public function test_user_cannot_see_created_event(): void
    {
        $event = Event::factory()->create(['user_id' => $this->user->id, 'realm_id' => $this->user->realm_id]);
        $response = $this->get('/events/'.$event->id);
        $response->assertStatus(404); // the view event page does not exist
    }

    public function test_user_can_edit_event(): void
    {
        $event = Event::factory()->create(['user_id' => $this->user->id, 'realm_id' => $this->user->realm_id]);
        $response = $this->get(route('events.edit', $event->id));
        $response->assertStatus(200);
    }

    public function test_user_can_create_event_with_template()
    {
        $template = Template::factory()->create(['user_id' => $this->user->id, 'realm_id' => $this->user->realm_id]);
        $response = $this->get(route('events.create.template', $template->id));
        $response->assertStatus(200);
    }

    // TEMPLATE
    public function test_user_can_create_template(): void
    {
        $response = $this->get(route('templates.create'));
        $response->assertStatus(200);
    }

    public function test_user_can_edit_template(): void
    {
        $template = Template::factory()->create(['user_id' => $this->user->id, 'realm_id' => $this->user->realm_id]);
        $response = $this->get(route('templates.edit', $template->id));
        $response->assertStatus(200);
    }

    // EVENTS_IMPORT
    public function test_user_can_not_create_events_import(): void
    {
        $response = $this->get(route('eventsImports.create'));
        $response->assertStatus(302);
    }

    public function test_user_can_not_edit_events_import(): void
    {
        $template = EventsImport::factory()->create(['user_id' => $this->user->id, 'realm_id' => $this->user->realm_id]);
        $response = $this->get(route('eventsImports.edit', $template->id));
        $response->assertStatus(302);
    }
}
