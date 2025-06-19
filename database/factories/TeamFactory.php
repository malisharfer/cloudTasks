<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TeamFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'commander_id' => User::factory(),
            'department_id' => Department::factory(),
        ];
    }
}
