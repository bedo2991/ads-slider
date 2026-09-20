<?php

namespace Database\Factories;

use App\Models\Picture;
use App\Models\Realm;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Schedule>
 */
class ScheduleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'scheduleable_type' => 'PI',
            'scheduleable_id' => Picture::factory(),
            'realm_id' => Realm::factory(),
            'user_id' => User::factory(),
            'start' => $this->faker->dateTimeBetween('-1 day', '+5 day')->format('Y-m-d'),
            'start_time' => $this->faker->time('H:i'),
            'end' => $this->faker->dateTimeBetween('now', '+1 years')->format('Y-m-d'),
            'end_time' => $this->faker->time('H:i'),
            'repeat' => rand(0, 7) === 0 ? null : strval(rand(1, 7)),
            'disabled' => $this->faker->boolean(10),
        ];
    }
}
