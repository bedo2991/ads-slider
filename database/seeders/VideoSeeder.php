<?php

namespace Database\Seeders;

use App\Models\Realm;
use App\Models\Schedule;
use App\Models\User;
use App\Models\Video;
use Illuminate\Database\Seeder;

class VideoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Video::factory()
            ->count(4)
            ->create([
                'user_id' => User::all()->random()->id,
                'realm_id' => Realm::all()->random()->id,
            ]);

        // Create Schedules for random Videos (simulating VideoSlides)
        $users = User::all();
        $videos = Video::all();

        if ($users->count() > 0 && $videos->count() > 0) {
            foreach (range(1, 5) as $i) {
                $video = $videos->random();
                Schedule::factory()->create([
                    'user_id' => $users->random()->id,
                    'scheduleable_id' => $video->id,
                    'scheduleable_type' => 'VI',
                    'realm_id' => $video->realm_id,
                ]);
            }
        }
    }
}
