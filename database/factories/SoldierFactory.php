<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SoldierFactory extends Factory
{
    public function definition(): array
    {
        return [
            'team_id' => null,
            'gender' => fake()->boolean(),
            'is_permanent' => fake()->boolean(),
            'enlist_date' => fake()->dateTime(),
            'course' => fake()->numberBetween(0, 500),
            'has_exemption' => fake()->boolean(),
            'max_shifts' => fake()->numberBetween(0, 50),
            'max_nights' => fake()->numberBetween(0, 31),
            'max_weekends' => fake()->numberBetween(0, 5),
            'capacity' => fake()->numberBetween(0, 12) / 4.0,
            'is_trainee' => fake()->boolean(),
            'is_mabat' => fake()->boolean(),
            'is_reservist' => fake()->boolean(),
            'qualifications' => fake(),
        ];
    }
}
