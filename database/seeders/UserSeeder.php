<?php

namespace Database\Seeders;

use App\Models\Realm;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::factory()
            ->count(3)
            ->hasEvents(10)
            ->hasTemplates(3)
            ->hasMonitors(2)
            ->create(
                [
                    'realm_id' => Realm::all()->random()->id,
                ]
            );

        // Add a known email address to the user with ID=1;
        $admin = User::find(1);
        $admin->email = 'admin@example.org';
        $admin->user_type = 'admin';
        $admin->save();
    }
}
