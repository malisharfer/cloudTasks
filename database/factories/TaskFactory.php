<?php

namespace Database\Factories;

use App\Enums\TaskKind;
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
            'kind' => TaskKind::cases()[random_int(0, count(TaskKind::cases()) - 1)]->value,
            'department_name' => fake()->name(),
            'recurring' => json_encode([]),
        ];
    }
}
