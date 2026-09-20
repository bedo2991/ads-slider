<?php

namespace Database\Seeders;

use App\Models\Realm;
use Illuminate\Database\Seeder;

class RealmSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Realm::factory()
            ->count(2)
            ->create();
    }
}
