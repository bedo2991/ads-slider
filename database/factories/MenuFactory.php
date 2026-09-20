<?php

namespace Database\Factories;

use App\Models\Realm;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MenuFactory extends Factory
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
            'path' => 'menu_'.$this->faker->randomNumber(5).'.json',
            'user_id' => User::factory(),
            'realm_id' => Realm::factory(),
        ];
    }
}
