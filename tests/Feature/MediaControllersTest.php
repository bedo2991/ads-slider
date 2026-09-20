<?php

namespace Tests\Feature;

use App\Events\SecurityAuditEvent;
use App\Http\Controllers\PictureSlideController;
use App\Http\Controllers\VideoSlideController;
use App\Livewire\CreateMenu;
use App\Models\Menu;
use App\Models\Monitor;
use App\Models\Picture;
use App\Models\Realm;
use App\Models\Schedule;
use App\Models\User;
use App\Models\Video;
use App\Providers\ItemUpdated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class MediaControllersTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Realm $realm;

    private Monitor $monitor;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->realm = Realm::factory()->create();
        $this->user = User::factory()->create([
            'user_type' => 'member',
            'realm_id' => $this->realm->id,
        ]);
        $this->monitor = Monitor::factory()->create([
            'realm_id' => $this->realm->id,
            'user_id' => $this->user->id,
        ]);

        $this->actingAs($this->user);
    }

    // --- PICTURE CONTROLLER TESTS ---

    public function test_picture_index_and_create_views(): void
    {
        $response = $this->get(route('pics.index'));
        $response->assertStatus(200);
        $response->assertViewIs('pics.index');

        $response = $this->get(route('pics.create'));
        $response->assertStatus(200);
        $response->assertViewIs('pics.create');
        $response->assertViewHas('monitors');
    }

    public function test_picture_store_creates_picture_and_source(): void
    {
        $image = UploadedFile::fake()->image('test_pic.jpg', 800, 600);

        $response = $this->post(route('pics.store'), [
            'name' => 'Sunset Overdrive',
            'duration' => 20,
            'upload' => $image,
            'monitors' => [$this->monitor->id],
        ]);

        $response->assertRedirect(route('pics.index'));

        $picture = Picture::where('name', 'Sunset Overdrive')->first();
        $this->assertNotNull($picture);
        $this->assertEquals(20, $picture->duration);
        $this->assertEquals($this->realm->id, $picture->realm_id);
        $this->assertNotNull($picture->bg_color);
        $this->assertNotNull($picture->color);

        $this->assertCount(1, $picture->sources);
        $this->assertEquals(800, $picture->sources->first()->width);
        $this->assertEquals(600, $picture->sources->first()->height);

        $this->assertTrue($picture->monitors->contains($this->monitor->id));
        Storage::disk('public')->assertExists(config('ads.pic_basepath').$picture->sources->first()->path);
    }

    public function test_picture_edit_and_update(): void
    {
        Event::fake([ItemUpdated::class]);

        $picture = Picture::factory()->create([
            'user_id' => $this->user->id,
            'realm_id' => $this->realm->id,
            'name' => 'Old Name',
            'duration' => 15,
        ]);

        // Create a slide for this picture to verify event broadcast
        Schedule::factory()->create([
            'scheduleable_type' => 'PI',
            'scheduleable_id' => $picture->id,
            'realm_id' => $this->realm->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->get(route('pics.edit', $picture->id));
        $response->assertStatus(200);
        $response->assertViewIs('pics.edit');

        $response = $this->put(route('pics.update', $picture->id), [
            'name' => 'Updated Picture Name',
            'duration' => 30,
            'color' => '#112233',
            'bg_color' => '#445566',
            'clock_location' => 3,
            'monitors' => [$this->monitor->id],
        ]);

        $response->assertRedirect(route('pics.index'));

        $picture->refresh();
        $this->assertEquals('Updated Picture Name', $picture->name);
        $this->assertEquals(30, $picture->duration);
        $this->assertEquals('#112233', $picture->color);
        $this->assertEquals('#445566', $picture->bg_color);
        $this->assertEquals(3, $picture->sources->first()->clock_location);

        Event::assertDispatched(ItemUpdated::class);
    }

    public function test_picture_destroy_deletes_files_and_dispatches_audit_event(): void
    {
        Event::fake([SecurityAuditEvent::class]);

        $picture = Picture::factory()->create([
            'user_id' => $this->user->id,
            'realm_id' => $this->realm->id,
            'name' => 'To Delete Picture',
        ]);

        $source = $picture->sources->first();
        Storage::disk('public')->put(config('ads.pic_basepath').$source->path, 'image-content');

        $response = $this->delete(route('pics.destroy', $picture->id));
        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);

        $this->assertDatabaseMissing('pictures', ['id' => $picture->id]);
        Storage::disk('public')->assertMissing(config('ads.pic_basepath').$source->path);

        Event::assertDispatched(SecurityAuditEvent::class, function (SecurityAuditEvent $event) use ($picture) {
            return $event->action === 'picture.deleted' && $event->context['picture_id'] === $picture->id;
        });
    }

    public function test_picture_sources_management(): void
    {
        $picture = Picture::factory()->create([
            'user_id' => $this->user->id,
            'realm_id' => $this->realm->id,
        ]);
        $firstSource = $picture->sources->first();

        // 1. Add second source
        $secondImage = UploadedFile::fake()->image('second.png', 1920, 1080);
        $response = $this->post(route('pics.storeSource', $picture->id), [
            'upload' => $secondImage,
        ]);
        $response->assertRedirect(route('pics.edit', $picture->id));
        $this->assertEquals(2, $picture->sources()->count());

        $secondSource = $picture->sources()->where('id', '!=', $firstSource->id)->first();

        // 2. Update source clock location
        $response = $this->put(route('pics.updateSource', $secondSource->id), [
            'clock_location' => 8,
        ]);
        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);
        $this->assertEquals(8, $secondSource->fresh()->clock_location);

        // 3. Delete second source
        $response = $this->delete(route('pics.destroySource', $secondSource->id));
        $response->assertRedirect();
        $this->assertEquals(1, $picture->sources()->count());

        // 4. Try deleting the only remaining source (should be rejected)
        $response = $this->delete(route('pics.destroySource', $firstSource->id));
        $response->assertRedirect();
        $this->assertEquals(1, $picture->sources()->count());
    }

    // --- VIDEO CONTROLLER TESTS ---

    public function test_video_index_and_create_views(): void
    {
        $response = $this->get(route('videos.index'));
        $response->assertStatus(200);
        $response->assertViewIs('videos.index');

        $response = $this->get(route('videos.create'));
        $response->assertStatus(200);
        $response->assertViewIs('videos.create');
    }

    public function test_video_store_and_update(): void
    {
        Event::fake([ItemUpdated::class]);

        $videoFile = UploadedFile::fake()->create('clip.mp4', 500, 'video/mp4');

        $response = $this->post(route('videos.store'), [
            'name' => 'Promo Video',
            'upload' => $videoFile,
            'monitors' => [$this->monitor->id],
        ]);

        $response->assertRedirect(route('videos.index'));

        $video = Video::where('name', 'Promo Video')->first();
        $this->assertNotNull($video);
        $this->assertEquals($this->realm->id, $video->realm_id);
        $this->assertTrue($video->monitors->contains($this->monitor->id));
        Storage::disk('public')->assertExists(config('ads.vid_basepath').$video->path);

        // Edit view
        $response = $this->get(route('videos.edit', $video->id));
        $response->assertStatus(200);

        // Update video
        $response = $this->put(route('videos.update', $video->id), [
            'name' => 'Updated Promo Video',
            'color' => '#AABBCC',
            'bg_color' => '#DDEEFF',
            'clock_location' => 1,
            'monitors' => [$this->monitor->id],
        ]);

        $response->assertRedirect(route('videos.index'));
        $video->refresh();
        $this->assertEquals('Updated Promo Video', $video->name);
        $this->assertEquals('#AABBCC', $video->color);
        $this->assertEquals(1, $video->clock_location);
    }

    public function test_video_destroy_dispatches_audit_event(): void
    {
        Event::fake([SecurityAuditEvent::class]);

        $video = Video::factory()->create([
            'user_id' => $this->user->id,
            'realm_id' => $this->realm->id,
            'name' => 'Video To Delete',
        ]);

        $response = $this->delete(route('videos.destroy', $video->id));
        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);

        $this->assertDatabaseMissing('videos', ['id' => $video->id]);

        Event::assertDispatched(SecurityAuditEvent::class, function (SecurityAuditEvent $event) use ($video) {
            return $event->action === 'video.deleted' && $event->context['video_id'] === $video->id;
        });
    }

    // --- MENU CONTROLLER TESTS ---

    public function test_menu_index_and_create_views(): void
    {
        $response = $this->get(route('menus.index'));
        $response->assertStatus(200);
        $response->assertViewIs('menus.index');

        $response = $this->get(route('menus.create'));
        $response->assertStatus(200);
        $response->assertViewIs('menus.create');
    }

    public function test_menu_store_and_update_and_show(): void
    {
        $jsonFile = UploadedFile::fake()->createWithContent('menu.json', json_encode(['items' => ['Cocktail A', 'Beer B']]));

        Livewire::actingAs($this->user)
            ->test(CreateMenu::class)
            ->set('name', 'Drinks Menu')
            ->set('upload', $jsonFile)
            ->set('monitors', [$this->monitor->id])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('menus.index'));

        $menu = Menu::where('name', 'Drinks Menu')->first();
        $this->assertNotNull($menu);
        $this->assertEquals($this->realm->id, $menu->realm_id);
        Storage::disk('public')->assertExists(config('ads.menu_basepath').$menu->path);

        // Edit view
        $response = $this->get(route('menus.edit', $menu->id));
        $response->assertStatus(200);

        // Show endpoint
        $response = $this->get(route('menus.show', $menu->id));
        $response->assertStatus(200);

        // Update menu
        $updatedJson = json_encode(['items' => ['Cocktail Premium', 'Wine C']]);
        $response = $this->put(route('menus.update', $menu->id), [
            'name' => 'Updated Drinks Menu',
            'menu_content' => $updatedJson,
            'monitors' => [$this->monitor->id],
        ]);

        $response->assertRedirect(route('menus.index'));
        $menu->refresh();
        $this->assertEquals('Updated Drinks Menu', $menu->name);
        $this->assertEquals($updatedJson, Storage::disk('public')->get(config('ads.menu_basepath').$menu->path));
    }

    public function test_menu_destroy_deletes_file_and_dispatches_audit_event(): void
    {
        Event::fake([SecurityAuditEvent::class]);

        $menu = Menu::factory()->create([
            'user_id' => $this->user->id,
            'realm_id' => $this->realm->id,
            'name' => 'Menu To Delete',
            'path' => 'menu_delete.json',
        ]);
        Storage::disk('public')->put(config('ads.menu_basepath').$menu->path, '{"test":true}');

        $response = $this->delete(route('menus.destroy', $menu->id));
        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);

        $this->assertDatabaseMissing('menus', ['id' => $menu->id]);
        Storage::disk('public')->assertMissing(config('ads.menu_basepath').$menu->path);

        Event::assertDispatched(SecurityAuditEvent::class, function (SecurityAuditEvent $event) use ($menu) {
            return $event->action === 'menu.deleted' && $event->context['menu_id'] === $menu->id;
        });
    }

    // --- SLIDE CONTROLLERS (PICTURE SLIDES & VIDEO SLIDES) ---

    public function test_picture_and_video_slide_controllers_views(): void
    {
        $picture = Picture::factory()->create([
            'user_id' => $this->user->id,
            'realm_id' => $this->realm->id,
        ]);
        $video = Video::factory()->create([
            'user_id' => $this->user->id,
            'realm_id' => $this->realm->id,
        ]);

        $picSlide = Schedule::factory()->create([
            'scheduleable_type' => 'PI',
            'scheduleable_id' => $picture->id,
            'realm_id' => $this->realm->id,
            'user_id' => $this->user->id,
        ]);

        $vidSlide = Schedule::factory()->create([
            'scheduleable_type' => 'VI',
            'scheduleable_id' => $video->id,
            'realm_id' => $this->realm->id,
            'user_id' => $this->user->id,
        ]);

        // Picture Slides
        $response = $this->get(route('picSlides.index'));
        $response->assertStatus(200);

        $this->withoutExceptionHandling();
        $response = $this->get(route('picSlides.create'));
        $response->assertStatus(200);

        $response = $this->get('/picSlides/create/'.$picture->id);
        $response->assertStatus(200);

        $response = $this->get(route('picSlides.edit', $picSlide->id));
        $response->assertStatus(200);

        // Video Slides
        $response = $this->get(route('vidSlides.index'));
        $response->assertStatus(200);

        $response = $this->get(route('vidSlides.create'));
        $response->assertStatus(200);

        $response = $this->get('/vidSlides/create/'.$video->id);
        $response->assertStatus(200);

        $response = $this->get(route('vidSlides.edit', $vidSlide->id));
        $response->assertStatus(200);
    }

    public function test_get_scheduled_media_on_monitor_helpers(): void
    {
        $picture = Picture::factory()->create([
            'user_id' => $this->user->id,
            'realm_id' => $this->realm->id,
        ]);
        $video = Video::factory()->create([
            'user_id' => $this->user->id,
            'realm_id' => $this->realm->id,
        ]);

        // Active schedules
        Schedule::factory()->create([
            'scheduleable_type' => 'PI',
            'scheduleable_id' => $picture->id,
            'realm_id' => $this->realm->id,
            'user_id' => $this->user->id,
            'start' => now()->subDay()->format('Y-m-d'),
            'end' => now()->addDay()->format('Y-m-d'),
            'disabled' => false,
        ]);

        Schedule::factory()->create([
            'scheduleable_type' => 'VI',
            'scheduleable_id' => $video->id,
            'realm_id' => $this->realm->id,
            'user_id' => $this->user->id,
            'start' => now()->subDay()->format('Y-m-d'),
            'end' => now()->addDay()->format('Y-m-d'),
            'disabled' => false,
        ]);

        $this->monitor->show_pictures = true;
        $this->monitor->show_videos = true;
        $this->monitor->stats = [
            'screen' => [
                'availWidth' => 1920,
                'availHeight' => 1080,
            ],
        ];
        $this->monitor->save();

        $pics = PictureSlideController::getScheduledPicturesOnMonitor($this->monitor);
        $this->assertNotNull($pics);
        $this->assertGreaterThanOrEqual(1, $pics->count());

        $videos = VideoSlideController::getScheduledVideosOnMonitor($this->monitor);
        $this->assertNotNull($videos);
        $this->assertGreaterThanOrEqual(1, $videos->get()->count());
    }
}
