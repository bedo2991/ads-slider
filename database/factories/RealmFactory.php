<?php

namespace Database\Factories;

use App\Enums\ScheduledSlideType;
use Illuminate\Database\Eloquent\Factories\Factory;

class RealmFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->sentence(2),
            'marketing_sentences' => [
                'Lust hinter der Theke zu stehen? Komm zur Versammlung vorbei!',
                'Would you like to try working behind the bar? Visit us during our weekly meeting!',
            ],
            'schedule' => ScheduledSlideType::defaultSchedule(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
