<?php

namespace Database\Factories;

use App\Models\Picture;
use App\Models\PictureSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PictureSource>
 */
class PictureSourceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'picture_id' => Picture::factory(),
            'path' => 'nostraw.jpg',
            'width' => 1920,
            'height' => 1080,
            'clock_location' => $this->faker->numberBetween(0, 9),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
