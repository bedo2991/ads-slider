<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\Event;
use App\Models\EventsImport;
use App\Models\HappyHour;
use App\Models\Menu;
use App\Models\Monitor;
use App\Models\Picture;
use App\Models\Realm;
use App\Models\Schedule;
use App\Models\Template;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class AuthorizationPoliciesTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    private User $userRealmA;

    private User $userRealmB;

    private Realm $realmA;

    private Realm $realmB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->realmA = Realm::factory()->create();
        $this->realmB = Realm::factory()->create();

        $this->adminUser = User::factory()->create([
            'user_type' => 'admin',
            'realm_id' => $this->realmA->id,
        ]);

        $this->userRealmA = User::factory()->create([
            'user_type' => 'member',
            'realm_id' => $this->realmA->id,
        ]);

        $this->userRealmB = User::factory()->create([
            'user_type' => 'member',
            'realm_id' => $this->realmB->id,
        ]);
    }

    public function test_alert_policy_allows_admin_and_denies_member(): void
    {
        $this->assertTrue(Gate::forUser($this->adminUser)->allows('create', Alert::class));
        $this->assertFalse(Gate::forUser($this->userRealmA)->allows('create', Alert::class));
    }

    public function test_events_import_policy_permissions(): void
    {
        $importA = EventsImport::factory()->create([
            'user_id' => $this->userRealmA->id,
            'realm_id' => $this->realmA->id,
        ]);

        // Admin can do anything
        $this->assertTrue(Gate::forUser($this->adminUser)->allows('view', $importA));
        $this->assertTrue(Gate::forUser($this->adminUser)->allows('create', EventsImport::class));
        $this->assertTrue(Gate::forUser($this->adminUser)->allows('update', $importA));
        $this->assertTrue(Gate::forUser($this->adminUser)->allows('delete', $importA));

        // Member cannot create
        $this->assertFalse(Gate::forUser($this->userRealmA)->allows('create', EventsImport::class));

        // Member of realm A can view and update own realm import
        $this->assertTrue(Gate::forUser($this->userRealmA)->allows('view', $importA));
        $this->assertTrue(Gate::forUser($this->userRealmA)->allows('update', $importA));

        // Member of realm B cannot view or update realm A import
        $this->assertFalse(Gate::forUser($this->userRealmB)->allows('view', $importA));
        $this->assertFalse(Gate::forUser($this->userRealmB)->allows('update', $importA));
    }

    public function test_realm_policy_permissions(): void
    {
        $realmAdmin = User::factory()->create([
            'user_type' => 'realm_admin',
            'realm_id' => $this->realmA->id,
        ]);

        // Admin can update any realm
        $this->assertTrue(Gate::forUser($this->adminUser)->allows('update', $this->realmA));
        $this->assertTrue(Gate::forUser($this->adminUser)->allows('update', $this->realmB));

        // Realm Admin can update own realm
        $this->assertTrue(Gate::forUser($realmAdmin)->allows('view', $this->realmA));
        $this->assertTrue(Gate::forUser($realmAdmin)->allows('update', $this->realmA));
        $this->assertFalse(Gate::forUser($realmAdmin)->allows('update', $this->realmB));

        // Member can view own realm but cannot update it
        $this->assertTrue(Gate::forUser($this->userRealmA)->allows('view', $this->realmA));
        $this->assertFalse(Gate::forUser($this->userRealmA)->allows('update', $this->realmA));

        // Member cannot view or update other realm
        $this->assertFalse(Gate::forUser($this->userRealmA)->allows('view', $this->realmB));
        $this->assertFalse(Gate::forUser($this->userRealmA)->allows('update', $this->realmB));
    }

    public function test_user_policy_permissions(): void
    {
        $otherUser = User::factory()->create(['realm_id' => $this->realmA->id]);

        // Admin can view/edit any user
        $this->assertTrue(Gate::forUser($this->adminUser)->allows('view', $otherUser));
        $this->assertTrue(Gate::forUser($this->adminUser)->allows('update', $otherUser));

        // User can view and update self
        $this->assertTrue(Gate::forUser($this->userRealmA)->allows('view', $this->userRealmA));
        $this->assertTrue(Gate::forUser($this->userRealmA)->allows('update', $this->userRealmA));

        // User cannot view or update other user
        $this->assertFalse(Gate::forUser($this->userRealmA)->allows('view', $otherUser));
        $this->assertFalse(Gate::forUser($this->userRealmA)->allows('update', $otherUser));
    }

    public function test_menu_policy_tenant_isolation(): void
    {
        $menuA = Menu::factory()->create(['realm_id' => $this->realmA->id]);

        $this->assertTrue(Gate::forUser($this->userRealmA)->allows('view', $menuA));
        $this->assertTrue(Gate::forUser($this->userRealmA)->allows('update', $menuA));
        $this->assertTrue(Gate::forUser($this->userRealmA)->allows('delete', $menuA));

        $this->assertFalse(Gate::forUser($this->userRealmB)->allows('view', $menuA));
        $this->assertFalse(Gate::forUser($this->userRealmB)->allows('update', $menuA));
        $this->assertFalse(Gate::forUser($this->userRealmB)->allows('delete', $menuA));
    }

    public function test_picture_and_video_policy_tenant_isolation(): void
    {
        $pictureA = Picture::factory()->create(['realm_id' => $this->realmA->id]);
        $videoA = Video::factory()->create(['realm_id' => $this->realmA->id]);

        $this->assertTrue(Gate::forUser($this->userRealmA)->allows('view', $pictureA));
        $this->assertTrue(Gate::forUser($this->userRealmA)->allows('update', $pictureA));
        $this->assertTrue(Gate::forUser($this->userRealmA)->allows('delete', $pictureA));

        $this->assertFalse(Gate::forUser($this->userRealmB)->allows('view', $pictureA));
        $this->assertFalse(Gate::forUser($this->userRealmB)->allows('update', $pictureA));
        $this->assertFalse(Gate::forUser($this->userRealmB)->allows('delete', $pictureA));

        $this->assertTrue(Gate::forUser($this->userRealmA)->allows('view', $videoA));
        $this->assertTrue(Gate::forUser($this->userRealmA)->allows('update', $videoA));
        $this->assertTrue(Gate::forUser($this->userRealmA)->allows('delete', $videoA));

        $this->assertFalse(Gate::forUser($this->userRealmB)->allows('view', $videoA));
        $this->assertFalse(Gate::forUser($this->userRealmB)->allows('update', $videoA));
        $this->assertFalse(Gate::forUser($this->userRealmB)->allows('delete', $videoA));
    }

    public function test_monitor_policy_tenant_isolation(): void
    {
        $monitorA = Monitor::factory()->create(['realm_id' => $this->realmA->id]);

        $this->assertTrue(Gate::forUser($this->userRealmA)->allows('view', $monitorA));
        $this->assertTrue(Gate::forUser($this->userRealmA)->allows('update', $monitorA));
        $this->assertFalse(Gate::forUser($this->userRealmA)->allows('delete', $monitorA));

        $this->assertFalse(Gate::forUser($this->userRealmB)->allows('view', $monitorA));
        $this->assertFalse(Gate::forUser($this->userRealmB)->allows('update', $monitorA));
        $this->assertFalse(Gate::forUser($this->userRealmB)->allows('delete', $monitorA));
    }

    public function test_event_and_template_and_schedule_policy_tenant_isolation(): void
    {
        $eventA = Event::factory()->create([
            'user_id' => $this->userRealmA->id,
            'realm_id' => $this->realmA->id,
        ]);
        $templateA = Template::factory()->create([
            'user_id' => $this->userRealmA->id,
            'realm_id' => $this->realmA->id,
        ]);
        $scheduleA = Schedule::factory()->create([
            'user_id' => $this->userRealmA->id,
            'realm_id' => $this->realmA->id,
        ]);

        $this->assertTrue(Gate::forUser($this->userRealmA)->allows('view', $eventA));
        $this->assertTrue(Gate::forUser($this->userRealmA)->allows('update', $eventA));
        $this->assertTrue(Gate::forUser($this->userRealmA)->allows('delete', $eventA));

        $this->assertFalse(Gate::forUser($this->userRealmB)->allows('view', $eventA));
        $this->assertFalse(Gate::forUser($this->userRealmB)->allows('update', $eventA));
        $this->assertFalse(Gate::forUser($this->userRealmB)->allows('delete', $eventA));

        $this->assertTrue(Gate::forUser($this->userRealmA)->allows('view', $templateA));
        $this->assertTrue(Gate::forUser($this->userRealmA)->allows('update', $templateA));
        $this->assertTrue(Gate::forUser($this->userRealmA)->allows('delete', $templateA));

        $this->assertFalse(Gate::forUser($this->userRealmB)->allows('view', $templateA));
        $this->assertFalse(Gate::forUser($this->userRealmB)->allows('update', $templateA));
        $this->assertFalse(Gate::forUser($this->userRealmB)->allows('delete', $templateA));

        $this->assertTrue(Gate::forUser($this->userRealmA)->allows('view', $scheduleA));
        $this->assertTrue(Gate::forUser($this->userRealmA)->allows('update', $scheduleA));

        $this->assertFalse(Gate::forUser($this->userRealmB)->allows('view', $scheduleA));
        $this->assertFalse(Gate::forUser($this->userRealmB)->allows('update', $scheduleA));
    }

    public function test_happy_hour_policy_tenant_isolation(): void
    {
        $eventA = Event::factory()->create(['realm_id' => $this->realmA->id]);
        $happyHourA = HappyHour::factory()->create(['event_id' => $eventA->id]);

        $this->assertTrue(Gate::forUser($this->userRealmA)->allows('view', $happyHourA));
        $this->assertTrue(Gate::forUser($this->userRealmA)->allows('update', $happyHourA));
        $this->assertTrue(Gate::forUser($this->userRealmA)->allows('delete', $happyHourA));

        $this->assertFalse(Gate::forUser($this->userRealmB)->allows('view', $happyHourA));
        $this->assertFalse(Gate::forUser($this->userRealmB)->allows('update', $happyHourA));
        $this->assertFalse(Gate::forUser($this->userRealmB)->allows('delete', $happyHourA));
    }
}
