<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'start_hour' => fake()->dateTime(),
            'duration' => fake()->randomDigit(),
            'parallel_weight' => fake()->randomDigit(),
            'type' => fake()->name(),
            'color' => fake()->hexColor(),
            'is_alert' => fake()->boolean(),
            'is_weekend' => fake()->boolean(),
            'is_night' => fake()->boolean(),
            'department_name' => fake()->name(),
            'recurring' => json_encode([]),
        ];
    }
}
