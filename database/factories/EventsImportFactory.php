<?php

namespace Database\Factories;

use App\Models\Realm;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventsImportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'import_name' => $this->faker->sentence(5),
            'place' => $this->faker->streetName(),
            'icon' => $this->faker->randomElement(['beer', 'star', 'clover', 'burst']),
            'not_closing' => $this->faker->boolean(20),
            'final_round_confirmed' => $this->faker->boolean(80),
            'is_karaoke' => $this->faker->boolean(1),
            'disabled' => $this->faker->boolean(10),
            'import_disabled' => $this->faker->boolean(10),
            'color' => $this->faker->hexcolor(),
            'import_url' => $this->faker->url(),
            'link' => $this->faker->boolean(50) ? $this->faker->url() : null,
            'user_id' => User::factory(),
            'realm_id' => Realm::factory(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
