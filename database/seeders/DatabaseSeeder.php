<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(RealmSeeder::class);
        $this->call(UserSeeder::class);
        $this->call(PictureSeeder::class);
        $this->call(VideoSeeder::class);
    }
}
