<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConstraintFactory extends Factory
{
    public function definition(): array
    {
        return [
            'soldier_id' => User::factory()->create()->userable_id,
            'constraint_type' => 'Not task',
            'start_date' => '2024-09-01 12:00:00',
            'end_date' => '2024-09-03 12:00:00',
        ];
    }
}
