<?php

namespace Tests\Unit;

use App\Models\Event;
use App\Models\Monitor;
use App\Models\Picture;
use App\Models\PictureSource;
use App\Models\Realm;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelsAndHelpersTest extends TestCase
{
    use RefreshDatabase;

    public function test_picture_get_best_source_aspect_ratio_selection(): void
    {
        $realm = Realm::factory()->create();
        $user = User::factory()->create(['realm_id' => $realm->id]);

        $picture = Picture::factory()->create([
            'realm_id' => $realm->id,
            'user_id' => $user->id,
        ]);
        $picture->sources()->delete();

        $landscapeSource = PictureSource::create([
            'picture_id' => $picture->id,
            'path' => 'pics/landscape.jpg',
            'width' => 1920,
            'height' => 1080,
            'clock_location' => 1,
            'color' => '#111111',
            'bg_color' => '#222222',
        ]);

        $portraitSource = PictureSource::create([
            'picture_id' => $picture->id,
            'path' => 'pics/portrait.jpg',
            'width' => 1080,
            'height' => 1920,
            'clock_location' => 9,
            'color' => '#333333',
            'bg_color' => '#444444',
        ]);

        $picture->load('sources');

        // 16:9 monitor should pick landscape
        $best = $picture->getBestSource(1920, 1080);
        $this->assertNotNull($best);
        $this->assertEquals($landscapeSource->id, $best->id);

        // 9:16 monitor should pick portrait
        $bestPortrait = $picture->getBestSource(1080, 1920);
        $this->assertNotNull($bestPortrait);
        $this->assertEquals($portraitSource->id, $bestPortrait->id);

        // 0 dimensions fallback
        $bestFallback = $picture->getBestSource(0, 0);
        $this->assertNotNull($bestFallback);

        // Path and clock location with monitor dimensions
        $picture->setMonitorDimensions(1920, 1080);
        $this->assertEquals('pics/landscape.jpg', $picture->path);
        $this->assertEquals(1, $picture->clock_location);

        $picture->setMonitorDimensions(1080, 1920);
        $this->assertEquals('pics/portrait.jpg', $picture->path);
        $this->assertEquals(9, $picture->clock_location);
    }

    public function test_schedule_duration_and_datetime_calculations(): void
    {
        // 1. Same-day event duration
        $duration = Schedule::calculateDuration(
            start: '2026-08-23',
            end: '2026-08-23',
            start_time: '10:00',
            end_time: '12:00',
            repeat: null
        );
        $this->assertNotNull($duration);
        $this->assertMatchesRegularExpression('/(2 hours|2 Stunden)/i', $duration);

        // 2. Cross-midnight duration
        $durationOvernight = Schedule::calculateDuration(
            start: '2026-08-23',
            end: '2026-08-24',
            start_time: '22:00',
            end_time: '02:00',
            repeat: null
        );
        $this->assertNotNull($durationOvernight);
        $this->assertMatchesRegularExpression('/(4 hours|4 Stunden)/i', $durationOvernight);

        // 3. Null handling
        $nullDuration = Schedule::calculateDuration(
            start: '2026-08-23',
            end: '2026-08-23',
            start_time: null,
            end_time: '12:00',
            repeat: null
        );
        $this->assertNull($nullDuration);

        // 4. Start & End DateTime calculation
        $startDt = Schedule::calculateStartDateTime(
            start: '2026-08-23',
            end: '2026-08-23',
            start_time: '14:30',
            end_time: '16:00',
            repeat: null
        );
        $this->assertEquals('2026-08-23 14:30:00', $startDt);

        $endDt = Schedule::calculateEndDateTime(
            start: '2026-08-23',
            end: '2026-08-23',
            start_time: '14:30',
            end_time: '16:00',
            repeat: null
        );
        $this->assertEquals('2026-08-23 16:00:59', $endDt);
    }

    public function test_realm_broadcast_channel_generation(): void
    {
        $realm = Realm::factory()->create(['id' => 42]);

        $channelName = Realm::getBroadcastChannel('events-updates', $realm->id);
        $this->assertStringStartsWith('events-updates-42-', $channelName);

        $secret = Realm::getBroadcastChannelSecret($realm->id);
        $this->assertNotEmpty($secret);
        $this->assertEquals(substr(hash_hmac('sha256', 'realm_'.$realm->id, config('app.key')), 0, 16), $secret);
    }

    public function test_event_start_time_and_expiration_helpers(): void
    {
        $realm = Realm::factory()->create();
        $user = User::factory()->create(['realm_id' => $realm->id]);

        $event = Event::factory()->create([
            'realm_id' => $realm->id,
            'user_id' => $user->id,
        ]);

        // Upcoming event
        $event->schedule()->delete();
        Schedule::create([
            'scheduleable_type' => 'EV',
            'scheduleable_id' => $event->id,
            'start' => now()->addDays(1)->format('Y-m-d'),
            'end' => now()->addDays(1)->format('Y-m-d'),
            'start_time' => '18:00:00',
            'end_time' => '22:00:00',
            'realm_id' => $realm->id,
            'user_id' => $user->id,
            'disabled' => false,
        ]);

        $event->refresh();
        $this->assertEquals('18:00', $event->start_time);
        $this->assertFalse($event->is_expired);

        // Expired event
        $pastEvent = Event::factory()->create([
            'realm_id' => $realm->id,
            'user_id' => $user->id,
        ]);

        $pastEvent->schedule()->delete();
        Schedule::create([
            'scheduleable_type' => 'EV',
            'scheduleable_id' => $pastEvent->id,
            'start' => now()->subDays(5)->format('Y-m-d'),
            'end' => now()->subDays(5)->format('Y-m-d'),
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'realm_id' => $realm->id,
            'user_id' => $user->id,
            'disabled' => false,
        ]);

        $pastEvent->refresh();
        $this->assertTrue($pastEvent->is_expired);
    }
}
