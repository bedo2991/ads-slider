<?php

namespace Database\Factories;

use App\Models\Realm;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class MonitorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->sentence(3),
            'events_to_show' => $this->faker->numberBetween(5, 10),
            'show_preparation_countdowns' => $this->faker->boolean(50),
            'show_final_rounds' => $this->faker->boolean(50),
            'show_we_are_closing' => $this->faker->boolean(50),
            'show_we_are_closed_marketing' => $this->faker->boolean(50),
            'show_cancelled_events' => $this->faker->boolean(50),
            'show_menus' => $this->faker->boolean(50),
            'show_happy_hours' => $this->faker->boolean(50),
            'show_pictures' => $this->faker->boolean(50),
            'show_karaoke' => $this->faker->boolean(20),
            'show_weather_forecast' => $this->faker->boolean(50),
            'use_animations' => $this->faker->boolean(70),
            'show_marquee' => $this->faker->boolean(50),
            'show_event_while_is_happening' => $this->faker->boolean(20),
            'realm_id' => Realm::factory(),
            'user_id' => User::factory(),
            'api_token' => Str::random(80),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
