<?php

namespace Tests\Feature;

use App\Events\SecurityAuditEvent;
use App\Models\EventsImport;
use App\Models\Realm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event as EventFacade;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rules\Password;
use Tests\TestCase;

class SecurityAuditTrailTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_update_dispatches_security_audit_event(): void
    {
        EventFacade::fake([SecurityAuditEvent::class]);

        $admin = User::factory()->create(['user_type' => 'admin']);
        $targetUser = User::factory()->create(['user_type' => 'member', 'realm_id' => $admin->realm_id]);
        $newRealm = Realm::factory()->create();

        $response = $this->actingAs($admin)->put(route('users.update', $targetUser->id), [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'realm_id' => $newRealm->id,
        ]);

        $response->assertRedirect(route('users.edit', $targetUser->id));

        EventFacade::assertDispatched(SecurityAuditEvent::class, function (SecurityAuditEvent $event) use ($targetUser) {
            return $event->action === 'user.profile_updated'
                && $event->context['target_user_id'] === $targetUser->id;
        });
    }

    public function test_user_registration_dispatches_security_audit_event(): void
    {
        EventFacade::fake([SecurityAuditEvent::class]);

        $admin = User::factory()->create(['user_type' => 'admin']);
        $realm = Realm::factory()->create();

        Password::defaults(function () {
            return Password::min(8);
        });

        $response = $this->actingAs($admin)->post(route('register'), [
            'name' => 'New Member',
            'email' => 'newmember@example.com',
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!',
            'user_type' => 'member',
            'realm_id' => $realm->id,
        ]);

        $response->assertSessionHasNoErrors();

        EventFacade::assertDispatched(SecurityAuditEvent::class, function (SecurityAuditEvent $event) {
            return $event->action === 'user.registered'
                && $event->context['registered_user_email'] === 'newmember@example.com';
        });
    }

    public function test_manual_events_import_dispatches_security_audit_event(): void
    {
        EventFacade::fake([SecurityAuditEvent::class]);
        Http::fake();

        $admin = User::factory()->create(['user_type' => 'admin']);
        $import = EventsImport::factory()->create([
            'user_id' => $admin->id,
            'realm_id' => $admin->realm_id,
        ]);

        $response = $this->actingAs($admin)->post(route('eventsImports.run', $import->id));
        $response->assertStatus(200);

        EventFacade::assertDispatched(SecurityAuditEvent::class, function (SecurityAuditEvent $event) use ($import) {
            return $event->action === 'events_import.executed'
                && $event->context['import_id'] === $import->id;
        });
    }
}
