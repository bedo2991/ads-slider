<?php

namespace Database\Factories;

use App\Models\Realm;
use App\Models\Schedule;
use App\Models\Template;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TemplateFactory extends Factory
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
            // 'start_time' => $this->faker->time('H:i:s'), // Moved to Schedule
            // 'end_time' => $this->faker->time('H:i:s'), // Moved to Schedule
            'place' => $this->faker->streetName(),
            'icon' => 'beer',
            'not_closing' => $this->faker->boolean(20),
            'final_round_confirmed' => $this->faker->boolean(80),
            'is_karaoke' => $this->faker->boolean(1),
            'color' => $this->faker->hexcolor(),
            'link' => $this->faker->boolean(50) ? $this->faker->url() : null,
            'user_id' => User::factory(),
            'realm_id' => Realm::factory(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Template $template) {
            Schedule::factory()->create([
                'scheduleable_id' => $template->id,
                'scheduleable_type' => 'TE',
                'realm_id' => $template->realm_id,
                'user_id' => $template->user_id,
                'start' => null, // Templates usually don't have dates
                'end' => null,
                'start_time' => $this->faker->time('H:i:s'),
                'end_time' => $this->faker->time('H:i:s'),
                'repeat' => null,
                'disabled' => false,
            ]);
        });
    }
}
