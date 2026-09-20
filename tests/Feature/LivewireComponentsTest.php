<?php

namespace Tests\Feature;

use App\Events\SecurityAuditEvent;
use App\Livewire\CreateEvent;
use App\Livewire\CreateEventsImport;
use App\Livewire\EditHappyHour;
use App\Livewire\EditPictureSlide;
use App\Livewire\EditRealm;
use App\Livewire\EditTemplate;
use App\Livewire\EditVideoSlide;
use App\Livewire\EventsList;
use App\Livewire\PastEventsList;
use App\Livewire\UpdateEvent;
use App\Livewire\UpdateEventsImport;
use App\Models\Event;
use App\Models\EventsImport;
use App\Models\HappyHour;
use App\Models\Menu;
use App\Models\Picture;
use App\Models\Realm;
use App\Models\Schedule;
use App\Models\Template;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event as EventFacade;
use Livewire\Livewire;
use Tests\TestCase;

class LivewireComponentsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $member;

    private Realm $realm;

    protected function setUp(): void
    {
        parent::setUp();

        $this->realm = Realm::factory()->create();

        $this->admin = User::factory()->create([
            'user_type' => 'admin',
            'realm_id' => $this->realm->id,
        ]);

        $this->member = User::factory()->create([
            'user_type' => 'member',
            'realm_id' => $this->realm->id,
        ]);
    }

    // --- EVENT & EVENTS LIST LIVEWIRE TESTS ---

    public function test_create_event_livewire_component(): void
    {
        $menu = Menu::factory()->create(['realm_id' => $this->realm->id]);
        $template = Template::factory()->create([
            'realm_id' => $this->realm->id,
            'name' => 'Template Event',
            'color' => '#123456',
        ]);

        Livewire::actingAs($this->member)
            ->test(CreateEvent::class, [
                'allTemplates' => Template::ofRealm($this->realm->id)->get(),
                'template' => $template,
            ])
            ->assertSet('form.name', 'Template Event')
            ->assertSet('form.color', '#123456')
            ->set('form.name', 'Live Music Night')
            ->set('form.start_time', '19:00')
            ->set('form.end_time', '23:00')
            ->set('form.start', now()->format('Y-m-d'))
            ->set('form.end', now()->addDay()->format('Y-m-d'))
            ->set('form.menus', [$menu->id])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('events.index'));

        $event = Event::where('name', 'Live Music Night')->first();
        $this->assertNotNull($event);
        $this->assertEquals($this->realm->id, $event->realm_id);
        $this->assertTrue($event->menus->contains($menu->id));
        $this->assertNotNull($event->schedule);
        $this->assertEquals('19:00', substr($event->schedule->start_time, 0, 5));
    }

    public function test_update_event_livewire_component(): void
    {
        $event = Event::factory()->create([
            'user_id' => $this->member->id,
            'realm_id' => $this->realm->id,
            'name' => 'Original Event Name',
        ]);

        Livewire::actingAs($this->member)
            ->test(UpdateEvent::class, [
                'event' => $event,
                'avUpdating' => false,
            ])
            ->assertSet('form.name', 'Original Event Name')
            ->set('form.name', 'Modified Event Name')
            ->set('form.start_time', '20:00')
            ->set('form.end_time', '02:00')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('events.index'));

        $this->assertEquals('Modified Event Name', $event->fresh()->name);

        // Test API token refresh and remove
        Livewire::actingAs($this->member)
            ->test(UpdateEvent::class, [
                'event' => $event->fresh(),
                'avUpdating' => false,
            ])
            ->call('refreshApiToken')
            ->call('removeApiToken');

        // Test delete event
        EventFacade::fake([SecurityAuditEvent::class]);
        Livewire::actingAs($this->member)
            ->test(UpdateEvent::class, [
                'event' => $event->fresh(),
                'avUpdating' => false,
            ])
            ->call('deleteEvent')
            ->assertRedirect(route('events.index'));

        $this->assertDatabaseMissing('events', ['id' => $event->id]);
        EventFacade::assertDispatched(SecurityAuditEvent::class);
    }

    public function test_events_list_and_past_events_list_components(): void
    {
        EventFacade::fake([SecurityAuditEvent::class]);

        $event = Event::factory()->create([
            'user_id' => $this->member->id,
            'realm_id' => $this->realm->id,
            'name' => 'Karaoke Special',
        ]);

        // EventsList
        Livewire::actingAs($this->member)
            ->test(EventsList::class)
            ->set('search', 'Karaoke')
            ->assertSee('Karaoke Special')
            ->call('clearSearch')
            ->assertSet('search', '')
            ->call('deleteEvent', $event->id);

        $this->assertDatabaseMissing('events', ['id' => $event->id]);
        EventFacade::assertDispatched(SecurityAuditEvent::class);

        // PastEventsList
        Livewire::actingAs($this->member)
            ->test(PastEventsList::class)
            ->set('search', 'Old')
            ->call('clearSearch')
            ->assertSet('search', '');
    }

    // --- TEMPLATE LIVEWIRE TESTS ---

    public function test_edit_template_livewire_component(): void
    {
        $allMenus = Menu::ofRealm($this->realm->id)->get();

        // Create template
        Livewire::actingAs($this->member)
            ->test(EditTemplate::class, [
                'allMenus' => $allMenus,
                'template' => null,
            ])
            ->set('form.name', 'Weekly Pub Quiz')
            ->set('form.place', 'Main Stage')
            ->set('form.color', '#FF5500')
            ->set('form.start_time', '18:00')
            ->set('form.end_time', '21:00')
            ->call('createTemplate')
            ->assertHasNoErrors()
            ->assertRedirect(route('templates.index'));

        $template = Template::where('name', 'Weekly Pub Quiz')->first();
        $this->assertNotNull($template);

        // Update template
        Livewire::actingAs($this->member)
            ->test(EditTemplate::class, [
                'allMenus' => $allMenus,
                'template' => $template,
            ])
            ->set('form.name', 'Weekly Pub Quiz & Trivia')
            ->call('updateTemplate')
            ->assertHasNoErrors()
            ->assertRedirect(route('templates.index'));

        $this->assertEquals('Weekly Pub Quiz & Trivia', $template->fresh()->name);

        // Delete template
        Livewire::actingAs($this->member)
            ->test(EditTemplate::class, [
                'allMenus' => $allMenus,
                'template' => $template->fresh(),
            ])
            ->call('deleteTemplate')
            ->assertRedirect(route('templates.index'));

        $this->assertDatabaseMissing('templates', ['id' => $template->id]);
    }

    // --- HAPPY HOUR LIVEWIRE TESTS ---

    public function test_edit_happy_hour_livewire_component(): void
    {
        $event = Event::factory()->create([
            'user_id' => $this->member->id,
            'realm_id' => $this->realm->id,
        ]);

        // Create happy hour
        Livewire::actingAs($this->member)
            ->test(EditHappyHour::class, [
                'event' => $event,
                'isManagerUpdating' => false,
            ])
            ->set('hhForm.drink', 'Cocktails')
            ->set('hhForm.price', '5.00')
            ->set('hhForm.start', now()->addHour()->format('Y-m-d H:i:s'))
            ->set('hhForm.end', now()->addHours(3)->format('Y-m-d H:i:s'))
            ->call('createHappyHour')
            ->assertHasNoErrors();

        $happyHour = HappyHour::where('event_id', $event->id)->first();
        $this->assertNotNull($happyHour);
        $this->assertEquals('Cocktails', $happyHour->drink);

        // Update happy hour
        Livewire::actingAs($this->member)
            ->test(EditHappyHour::class, [
                'event' => $event->fresh(),
                'isManagerUpdating' => false,
            ])
            ->set('hhForm.drink', 'Beers')
            ->call('updateHappyHour')
            ->assertHasNoErrors();

        $this->assertEquals('Beers', $happyHour->fresh()->drink);

        // Delete happy hour
        Livewire::actingAs($this->member)
            ->test(EditHappyHour::class, [
                'event' => $event->fresh(),
                'isManagerUpdating' => false,
            ])
            ->call('deleteHappyHour');

        $this->assertDatabaseMissing('happy_hours', ['id' => $happyHour->id]);
    }

    // --- SLIDES LIVEWIRE TESTS ---

    public function test_edit_picture_slide_and_video_slide_components(): void
    {
        $picture = Picture::factory()->create(['realm_id' => $this->realm->id]);
        $video = Video::factory()->create(['realm_id' => $this->realm->id]);

        // Picture Slide Create
        Livewire::actingAs($this->member)
            ->test(EditPictureSlide::class, [
                'action' => 'create',
                'allPictures' => [$picture],
                'selected_picture' => $picture->id,
            ])
            ->set('form.start_time', '08:00')
            ->set('form.end_time', '22:00')
            ->call('createPictureSlide')
            ->assertHasNoErrors()
            ->assertRedirect(route('picSlides.index'));

        $picSlide = Schedule::where('scheduleable_type', 'PI')->where('scheduleable_id', $picture->id)->first();
        $this->assertNotNull($picSlide);

        // Picture Slide Update & Delete
        Livewire::actingAs($this->member)
            ->test(EditPictureSlide::class, [
                'action' => 'edit',
                'allPictures' => [$picture],
                'picSlide' => $picSlide,
            ])
            ->set('form.start_time', '09:00')
            ->call('updatePictureSlide')
            ->assertHasNoErrors()
            ->call('deletePictureSlide')
            ->assertRedirect(route('picSlides.index'));

        $this->assertDatabaseMissing('schedules', ['id' => $picSlide->id]);

        // Video Slide Create
        Livewire::actingAs($this->member)
            ->test(EditVideoSlide::class, [
                'allVideos' => [$video],
                'selected_video' => $video->id,
            ])
            ->set('form.start_time', '10:00')
            ->set('form.end_time', '20:00')
            ->call('createVideoSlide')
            ->assertHasNoErrors()
            ->assertRedirect(route('vidSlides.index'));

        $vidSlide = Schedule::where('scheduleable_type', 'VI')->where('scheduleable_id', $video->id)->first();
        $this->assertNotNull($vidSlide);

        // Video Slide Update & Delete
        Livewire::actingAs($this->member)
            ->test(EditVideoSlide::class, [
                'allVideos' => [$video],
                'vidSlide' => $vidSlide,
            ])
            ->set('form.start_time', '11:00')
            ->call('updateVideoSlide')
            ->assertHasNoErrors()
            ->call('deleteVideoSlide')
            ->assertRedirect(route('vidSlides.index'));

        $this->assertDatabaseMissing('schedules', ['id' => $vidSlide->id]);
    }

    // --- REALM & EVENTS IMPORT LIVEWIRE TESTS ---

    public function test_edit_realm_livewire_component(): void
    {
        EventFacade::fake([SecurityAuditEvent::class]);

        Livewire::actingAs($this->admin)
            ->test(EditRealm::class, [
                'realm' => $this->realm,
            ])
            ->set('form.name', 'Updated Realm Name')
            ->set('form.locale', 'it')
            ->set('form.ow_city_id', '2867714')
            ->call('updateRealm')
            ->assertHasNoErrors();

        $this->assertEquals('Updated Realm Name', $this->realm->fresh()->name);
        $this->assertEquals('it', $this->realm->fresh()->locale);

        // Refresh and Delete orders pull tokens
        Livewire::actingAs($this->admin)
            ->test(EditRealm::class, [
                'realm' => $this->realm->fresh(),
            ])
            ->call('refreshOrdersPull')
            ->call('deleteOrdersPull');

        $this->assertNull($this->realm->fresh()->orders_pull);
        EventFacade::assertDispatched(SecurityAuditEvent::class);
    }

    public function test_events_import_create_and_update_components(): void
    {
        EventFacade::fake([SecurityAuditEvent::class]);

        // Create import
        Livewire::actingAs($this->admin)
            ->test(CreateEventsImport::class)
            ->set('form.import_name', 'University Events Feed')
            ->set('form.import_url', 'https://events.example.com/feed.json')
            ->set('form.start_time', '10:00')
            ->set('form.end_time', '18:00')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('eventsImports.index'));

        $import = EventsImport::where('import_name', 'University Events Feed')->first();
        $this->assertNotNull($import);

        // Update import
        Livewire::actingAs($this->admin)
            ->test(UpdateEventsImport::class, [
                'eventsImport' => $import,
            ])
            ->set('form.import_name', 'Updated University Events Feed')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('eventsImports.index'));

        $this->assertEquals('Updated University Events Feed', $import->fresh()->import_name);

        // Delete import
        Livewire::actingAs($this->admin)
            ->test(UpdateEventsImport::class, [
                'eventsImport' => $import->fresh(),
            ])
            ->call('deleteEventsImport')
            ->assertRedirect(route('eventsImports.index'));

        $this->assertDatabaseMissing('events_imports', ['id' => $import->id]);
        EventFacade::assertDispatched(SecurityAuditEvent::class);
    }
}
