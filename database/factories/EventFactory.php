<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Realm;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventFactory extends Factory
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
            // Schedule fields moved to ScheduleFactory
            'place' => $this->faker->streetName(),
            'icon' => $this->faker->randomElement(['beer', 'star', 'clover', 'burst']),
            'not_closing' => $this->faker->boolean(20),
            'final_round_confirmed' => $this->faker->boolean(80),
            'is_karaoke' => $this->faker->boolean(1),
            // 'disabled' moved to Schedule
            'color' => $this->faker->hexcolor(),
            'user_id' => User::factory(),
            'realm_id' => Realm::factory(),
            'link' => $this->faker->boolean(50) ? $this->faker->url() : null,
            'api_token' => $this->faker->uuid(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Event $event) {
            Schedule::factory()->create([
                'scheduleable_id' => $event->id,
                'scheduleable_type' => 'EV',
                'realm_id' => $event->realm_id,
                'user_id' => $event->user_id,
                'start' => $this->faker->dateTimeBetween('-1 day', '+5 day')->format('Y-m-d'),
                'start_time' => $this->faker->time('H:i:s'),
                'end' => $this->faker->dateTimeBetween('now', '+1 years')->format('Y-m-d'),
                'end_time' => $this->faker->time('H:i:s'),
                'repeat' => rand(0, 7) === 0 ? null : strval(rand(1, 7)),
                'disabled' => $this->faker->boolean(10),
            ]);
        });
    }
}
