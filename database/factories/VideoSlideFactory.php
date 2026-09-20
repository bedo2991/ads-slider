<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class VideoSlideFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'start' => $this->faker->dateTimeBetween('-1 day', '+5 day')->format('Y-m-d'),
            'start_time' => $this->faker->time('H:i:s'),
            'end' => $this->faker->dateTimeBetween('now', '+1 years')->format('Y-m-d'),
            'end_time' => $this->faker->time('H:i:s'),
            'repeat' => rand(0, 7) === 0 ? null : strval(rand(1, 7)),
            'disabled' => $this->faker->boolean(10),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
