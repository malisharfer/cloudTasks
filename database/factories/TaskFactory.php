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
            'department_name' => fake()->text(),
            'recurrence' => json_encode([]),
        ];
    }
}
