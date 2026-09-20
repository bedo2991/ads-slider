<?php

namespace Database\Factories;

use App\Models\HappyHour;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HappyHour>
 */
class HappyHourFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'start' => $this->faker->dateTime(),
            'end' => $this->faker->dateTime(),
            'drink' => $this->faker->sentence(2),
            'price' => $this->faker->randomDigitNotZero().','.$this->faker->randomDigit().'0 €',
            'info' => $this->faker->sentence(2),
        ];
    }
}
