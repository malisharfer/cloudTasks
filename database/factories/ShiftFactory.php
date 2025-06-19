<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShiftFactory extends Factory
{
    public function definition(): array
    {
        return [
            'soldier_id' => User::factory()->create()->userable_id,
            'task_id' => Task::factory()->create()->id,
            'is_weekend' => fake()->boolean(),
            'parallel_weight' => fake()->randomDigit(),
            'start_date' => now(),
            'end_date' => Carbon::now()->addDay(),
        ];
    }
}
