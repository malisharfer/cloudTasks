<?php

namespace Database\Factories;

use App\Models\Soldier;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'first_name' => fake()->name(),
            'last_name' => fake()->name(),
            'password' => static::$password ?? Hash::make((string) fake()->randomNumber(7)),
            'userable_type' => Soldier::class,
            'userable_id' => Soldier::factory(),
        ];
    }
}
