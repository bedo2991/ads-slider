<?php

namespace Database\Seeders;

use App\Models\Picture;
use App\Models\Realm;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Database\Seeder;

class PictureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Picture::factory()
            ->count(4)
            ->create([
                'user_id' => User::all()->random()->id,
                'realm_id' => Realm::all()->random()->id,
            ]);

        // Create Schedules for random Pictures (simulating PictureSlides)
        $users = User::all();
        $pictures = Picture::all();

        if ($users->count() > 0 && $pictures->count() > 0) {
            foreach (range(1, 5) as $i) {
                $picture = $pictures->random();
                Schedule::factory()->create([
                    'user_id' => $users->random()->id,
                    'scheduleable_id' => $picture->id,
                    'scheduleable_type' => 'PI',
                    'realm_id' => $picture->realm_id,
                ]);
            }
        }
    }
}
