<?php

namespace Database\Factories;

use App\Models\Picture;
use App\Models\PictureSource;
use App\Models\Realm;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PictureFactory extends Factory
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
            'color' => $this->faker->hexColor(),
            'bg_color' => $this->faker->hexColor(),
            'duration' => 15,
            'user_id' => User::factory(),
            'realm_id' => Realm::factory(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Picture $picture) {
            if ($picture->sources()->count() === 0) {
                PictureSource::factory()->create([
                    'picture_id' => $picture->id,
                ]);
            }
        });
    }
}
