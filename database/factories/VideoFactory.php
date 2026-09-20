<?php

namespace Database\Factories;

use App\Models\Realm;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class VideoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->sentence(5),
            'path' => 'example.mp4',
            'color' => $this->faker->hexColor(),
            'bg_color' => $this->faker->hexColor(),
            'user_id' => User::factory(),
            'realm_id' => Realm::factory(),
            'clock_location' => $this->faker->numberBetween(0, 9),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
